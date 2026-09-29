<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\ContactMessage;
use App\Models\User;
use App\Services\CatalogService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),

            'auth' => [
                'user' => fn () => $this->userPayload($request->user()),
            ],

            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
            ],

            'locale' => app()->getLocale(),

            'settings' => [
                'site_name' => setting('site.name', setting('site.title', 'معرض أبو عفيفة للمفروشات')),
                'whatsapp' => setting('contact.whatsapp'),
                'phone' => setting('contact.phone'),
                'email' => setting('contact.email', setting('site.email')),
                'address' => setting('contact.address_ar'),
                'working_hours' => setting('contact.working_hours'),
                'map_lat' => setting('contact.map_lat', 29.5267),
                'map_lng' => setting('contact.map_lng', 35.0078),
                'facebook' => setting('social.facebook'),
                'instagram' => setting('social.instagram'),
                'tiktok' => setting('social.tiktok'),
            ],

            'categories' => fn () => app(CatalogService::class)->categoriesTree(),

            // Live unread contact-message count for the admin sidebar /
            // topbar badge. Only compiled for staff that may view messages;
            // everyone else just gets 0.
            'unreadMessagesCount' => (int) (
                $request->user()?->can('viewAny', ContactMessage::class)
                    ? ContactMessage::query()->unread()->count()
                    : 0
            ),
        ];
    }

    /**
     * A hand-picked projection of the user.
     *
     * Sharing the model itself would put every column that is not in
     * `$hidden` — phone number, activity flags, timestamps — into the
     * HTML of every page, including pages rendered for guests of the
     * same session. Only what the interface actually needs is sent.
     *
     * The permission list is for *rendering* decisions (hide a button
     * the user cannot use). It is never the thing that grants access:
     * every action re-checks its policy server-side.
     *
     * @return array<string, mixed>|null
     */
    private function userPayload(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'locale' => $user->locale,
            'is_staff' => $user->canAccessAdmin(),
            'must_change_password' => (bool) $user->must_change_password,
            'roles' => $user->getRoleNames(),
            'role_labels' => $user->getRoleNames()
                ->map(fn (string $name) => UserRole::tryFrom($name)?->label() ?? $name)
                ->values(),
            // Only sent to staff — a customer has no use for the list and
            // it would describe the back office to anyone signed in.
            'permissions' => $user->canAccessAdmin()
                ? $user->getAllPermissions()->pluck('name')->values()
                : [],
        ];
    }
}
