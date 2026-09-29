<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActivityAction;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Read-only view over the audit trail.
 *
 * There is no create or update action by design — the log is written
 * only by ActivityLogger, and pruning is limited to the retention
 * window so an administrator cannot quietly erase their own tracks.
 */
class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', ActivityLog::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'action' => ['nullable', 'string', 'max:80'],
            'group' => ['nullable', 'string', 'max:40'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'security_only' => ['nullable', 'boolean'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        $logs = ActivityLog::query()
            ->with('user:id,name,email')
            ->when($filters['action'] ?? null, fn ($q, $action) => $q->action($action))
            ->when($filters['group'] ?? null, fn ($q, $group) => $q->group($group))
            ->when($filters['user_id'] ?? null, fn ($q, $id) => $q->byUser((int) $id))
            ->when($request->boolean('security_only'), fn ($q) => $q->securityEvents())
            ->when($filters['search'] ?? null, function ($q, $search) {
                // Bound parameters throughout — never string interpolation.
                $q->where(fn ($w) => $w
                    ->where('description', 'like', "%{$search}%")
                    ->orWhere('subject_label', 'like', "%{$search}%")
                    ->orWhere('user_name', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%"));
            })
            ->between($filters['from'] ?? null, $filters['to'] ?? null)
            ->latest('created_at')
            ->paginate((int) ($filters['per_page'] ?? 25))
            ->withQueryString()
            ->through(fn (ActivityLog $log) => $this->present($log));

        return Inertia::render('Admin/Activity/Index', [
            'logs' => $logs,
            'filters' => $filters,
            'actions' => collect(ActivityAction::cases())
                ->map(fn (ActivityAction $a) => [
                    'value' => $a->value,
                    'label' => $a->label(),
                    'group' => $a->group(),
                ])->values(),
            'users' => User::query()
                ->where('is_admin', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function show(ActivityLog $activity)
    {
        Gate::authorize('view', $activity);

        return Inertia::render('Admin/Activity/Show', [
            'log' => $this->present($activity, detailed: true),
        ]);
    }

    /**
     * CSV export of the current filter, streamed so a large log does not
     * have to fit in memory.
     */
    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', ActivityLog::class);

        $query = ActivityLog::query()
            ->when($request->filled('group'), fn ($q) => $q->group($request->string('group')->toString()))
            ->when($request->boolean('security_only'), fn ($q) => $q->securityEvents())
            ->between($request->input('from'), $request->input('to'))
            ->latest('created_at');

        $filename = 'activity-log-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'wb');

            // BOM so Excel reads the Arabic labels as UTF-8.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['التاريخ', 'المستخدم', 'الدور', 'الإجراء', 'العنصر', 'IP', 'الرابط']);

            $query->chunk(500, function ($chunk) use ($handle) {
                foreach ($chunk as $log) {
                    fputcsv($handle, [
                        $log->created_at?->format('Y-m-d H:i:s'),
                        $log->user_name,
                        $log->user_role,
                        $log->action_label,
                        $log->subject_label,
                        $log->ip_address,
                        $log->url,
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Delete entries older than the configured retention window.
     *
     * Only entries past the window can go: there is no way to delete
     * recent history, which is what an attacker covering their tracks
     * would want.
     */
    public function purge(Request $request)
    {
        Gate::authorize('purge', ActivityLog::class);

        $retentionDays = (int) config('security.activity_log.retention_days', 365);

        if ($retentionDays <= 0) {
            return back()->withErrors(['error' => 'حذف السجلات معطّل في الإعدادات.']);
        }

        $cutoff = now()->subDays($retentionDays);
        $deleted = ActivityLog::query()->where('created_at', '<', $cutoff)->delete();

        activity()->log(
            ActivityAction::ActivityLogPurged,
            null,
            ['purged_rows' => $deleted, 'older_than' => $cutoff->toDateTimeString()],
            description: "تم حذف {$deleted} سجل أقدم من {$retentionDays} يوم",
        );

        return back()->with('success', "تم حذف {$deleted} سجلاً قديماً.");
    }

    /**
     * @return array<string, mixed>
     */
    private function present(ActivityLog $log, bool $detailed = false): array
    {
        return [
            'id' => $log->id,
            'action' => $log->action,
            'action_label' => $log->action_label,
            'group' => $log->action_group,
            'description' => $log->description,
            'user' => [
                'id' => $log->user_id,
                'name' => $log->user_name,
                'email' => $log->user_email,
                'role' => $log->user_role,
            ],
            'subject' => [
                'type' => $log->subject_type ? class_basename($log->subject_type) : null,
                'id' => $log->subject_id,
                'label' => $log->subject_label,
            ],
            'ip_address' => $log->ip_address,
            'is_security_event' => $log->is_security_event,
            'created_at' => $log->created_at?->toDateTimeString(),
            'created_at_human' => $log->created_at?->diffForHumans(),
            ...$detailed ? [
                'properties' => $log->properties,
                'user_agent' => $log->user_agent,
                'method' => $log->method,
                'url' => $log->url,
                'route_name' => $log->route_name,
            ] : [],
        ];
    }
}
