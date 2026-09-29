<?php

namespace App\Models\Concerns;

use App\Enums\ActivityAction;
use App\Models\ActivityLog;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Wires a model's lifecycle events into the audit trail.
 *
 * A model using this trait must declare which ActivityAction belongs to
 * each event:
 *
 *     protected static function activityActions(): array
 *     {
 *         return [
 *             'created' => ActivityAction::ProductCreated,
 *             'updated' => ActivityAction::ProductUpdated,
 *             'deleted' => ActivityAction::ProductDeleted,
 *             'restored' => ActivityAction::ProductRestored,
 *             'forceDeleted' => ActivityAction::ProductForceDeleted,
 *         ];
 *     }
 *
 * Events without a mapping are simply not logged.
 */
trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            $model->recordActivity('created', ['attributes' => $model->auditableAttributes()]);
        });

        static::updated(function ($model) {
            $changes = app(ActivityLogger::class)->diff(
                $model->getOriginal(),
                collect($model->getChanges())->except($model->auditExcept())->all(),
            );

            // Nothing meaningful moved (e.g. a timestamp touch) — stay quiet.
            if ($changes === []) {
                return;
            }

            $model->recordActivity('updated', $changes);
        });

        static::deleted(function ($model) {
            $forced = method_exists($model, 'isForceDeleting') && $model->isForceDeleting();

            $model->recordActivity(
                $forced ? 'forceDeleted' : 'deleted',
                ['attributes' => $model->auditableAttributes()],
            );
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                $model->recordActivity('restored');
            });
        }
    }

    /**
     * Map a lifecycle event onto an ActivityAction.
     *
     * @return array<string, ActivityAction>
     */
    protected static function activityActions(): array
    {
        return [];
    }

    /**
     * Attributes excluded from the audit snapshot for this model.
     *
     * @return list<string>
     */
    protected function auditExcept(): array
    {
        return ['password', 'remember_token', 'created_at', 'updated_at'];
    }

    public function auditableAttributes(): array
    {
        return collect($this->getAttributes())
            ->except($this->auditExcept())
            ->all();
    }

    public function recordActivity(string $event, array $properties = []): ?ActivityLog
    {
        $action = static::activityActions()[$event] ?? null;

        if (! $action instanceof ActivityAction) {
            return null;
        }

        return app(ActivityLogger::class)->log($action, $this, $properties);
    }

    /**
     * Every audit row written about this record.
     */
    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject')->latest('created_at');
    }
}
