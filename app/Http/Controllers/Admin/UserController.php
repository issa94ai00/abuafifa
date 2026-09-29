<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActivityAction;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

/**
 * User administration.
 *
 * Every action authorises against UserPolicy, which is where the
 * anti-escalation rules live (rank comparison, self-protection, last
 * super admin). The route group already restricts this controller to
 * super admins; the policy calls stay because the perimeter and the
 * lock are different things and only one of them survives a route
 * refactor.
 */
class UserController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', User::class);

        $search = $request->string('search')->trim()->toString();

        $users = User::query()
            ->with('roles:id,name')
            ->when($search !== '', function ($q) use ($search) {
                // Bindings, not interpolation — the value never becomes SQL.
                $q->where(fn ($w) => $w
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%"));
            })
            ->when($request->filled('role'), fn ($q) => $q->role($request->string('role')->toString()))
            ->latest()
            ->paginate($request->integer('per_page', 15))
            ->withQueryString()
            ->through(fn (User $user) => $this->present($user, $request->user()));

        $stats = [
            'total'   => User::query()->count(),
            'active'  => User::query()->where('is_active', true)->count(),
            'staff'   => User::query()->where('is_admin', true)->count(),
            'monthly' => User::query()->where('created_at', '>=', now()->startOfMonth())->count(),
        ];

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'stats' => $stats,
            'filters' => ['search' => $search, 'role' => $request->input('role')],
            'roles' => Role::query()
                ->whereIn('name', UserRole::values())
                ->get(['id', 'name'])
                ->map(fn (Role $role) => [
                    'name' => $role->name,
                    'label' => UserRole::tryFrom($role->name)?->label() ?? $role->name,
                    // The UI hides roles the actor may not grant; the
                    // policy refuses them regardless of what is posted.
                    'assignable' => Gate::allows('mayGrant', [User::class, [$role->name]]),
                ]),
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        Gate::authorize('create', User::class);

        $data = $request->validated();
        $roles = $data['roles'] ?? [];

        // Refuse the whole request if any requested role is out of reach.
        abort_unless($request->user()->can('mayGrant', [User::class, $roles]), 403, __('auth.role_escalation'));

        $user = DB::transaction(function () use ($data, $roles) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],      // hashed by the model cast
                'locale' => $data['locale'] ?? 'ar',
                'is_active' => $data['is_active'] ?? true,
            ]);

            $user->forceFill([
                // Staff membership is granted here, never mass assigned.
                'is_admin' => $roles !== [],
                'password_changed_at' => now(),
                'must_change_password' => true,
            ])->save();

            $user->syncRoles($roles);

            return $user;
        });

        $this->logger->log(
            ActivityAction::UserCreated,
            $user,
            ['roles' => $roles, 'is_active' => $user->is_active],
        );

        return back()->with('success', 'تم إضافة المستخدم بنجاح.');
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        Gate::authorize('update', $user);

        $data = $request->validated();
        $actor = $request->user();
        $before = $user->getAttributes();
        $rolesBefore = $user->roles->pluck('name')->sort()->values()->all();

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'locale' => $data['locale'] ?? $user->locale,
        ]);

        // Deactivating an account is a separate ability so it cannot be
        // done to yourself, or to a peer, through the general edit form.
        if (array_key_exists('is_active', $data) && (bool) $data['is_active'] !== (bool) $user->is_active) {
            Gate::authorize('toggleActive', $user);

            $user->update(['is_active' => (bool) $data['is_active']]);

            $this->logger->log(
                $user->is_active ? ActivityAction::UserActivated : ActivityAction::UserDeactivated,
                $user,
            );
        }

        if (filled($data['password'] ?? null)) {
            $user->forceFill([
                'password' => Hash::make($data['password']),
                'password_changed_at' => now(),
                'must_change_password' => true,
            ])->save();

            // A password an administrator set must not leave live sessions
            // running on the old one.
            $this->invalidateSessionsFor($user);
        }

        if (array_key_exists('roles', $data)) {
            $roles = $data['roles'] ?? [];

            Gate::authorize('assignRoles', [$user, $roles]);

            if ($roles !== $rolesBefore) {
                $user->syncRoles($roles);
                $user->forceFill(['is_admin' => $roles !== []])->save();

                $this->logger->log(ActivityAction::UserRolesChanged, $user, [
                    'from' => $rolesBefore,
                    'to' => $roles,
                    'changed_by' => $actor->email,
                ]);
            }
        }

        $this->logger->log(
            ActivityAction::UserUpdated,
            $user,
            $this->logger->diff($before, $user->getAttributes()),
        );

        return back()->with('success', 'تم تحديث المستخدم بنجاح.');
    }

    /**
     * Toggle the active flag on its own endpoint, so the ability can be
     * granted and audited independently of general editing.
     */
    public function toggleActive(Request $request, User $user)
    {
        Gate::authorize('toggleActive', $user);

        $user->update(['is_active' => ! $user->is_active]);

        if (! $user->is_active) {
            $this->invalidateSessionsFor($user);
        }

        $this->logger->log(
            $user->is_active ? ActivityAction::UserActivated : ActivityAction::UserDeactivated,
            $user,
        );

        return back()->with('success', $user->is_active ? 'تم تفعيل الحساب.' : 'تم تعطيل الحساب.');
    }

    public function destroy(Request $request, User $user)
    {
        Gate::authorize('delete', $user);

        $snapshot = $this->logger->snapshot($user);
        $roles = $user->roles->pluck('name')->all();

        $this->invalidateSessionsFor($user);
        $user->delete();

        $this->logger->log(ActivityAction::UserDeleted, null, [
            'deleted_user' => [
                'id' => $snapshot['id'] ?? null,
                'name' => $snapshot['name'] ?? null,
                'email' => $snapshot['email'] ?? null,
                'roles' => $roles,
            ],
        ], description: 'حذف مستخدم: '.($snapshot['email'] ?? ''));

        return back()->with('success', 'تم حذف المستخدم.');
    }

    // ------------------------------------------------------------ helpers

    /**
     * Drop every stored session belonging to a user, so a deactivation,
     * deletion or forced password change takes effect immediately rather
     * than whenever their cookie happens to expire.
     */
    private function invalidateSessionsFor(User $user): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->getKey())
            ->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function present(User $user, User $actor): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'is_active' => $user->is_active,
            'is_admin' => $user->is_admin,
            'locale' => $user->locale ?? 'ar',
            'roles' => $user->roles->pluck('name'),
            'role_labels' => $user->roles->pluck('name')
                ->map(fn ($n) => UserRole::tryFrom($n)?->label() ?? $n),
            'last_login_at' => $user->last_login_at?->toDateTimeString(),
            'created_at' => $user->created_at?->toDateString(),
            // Drives the UI only; the server re-checks on every request.
            'can' => [
                'update' => $actor->can('update', $user),
                'delete' => $actor->can('delete', $user),
                'toggle_active' => $actor->can('toggleActive', $user),
            ],
        ];
    }
}
