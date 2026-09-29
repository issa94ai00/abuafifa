<?php

namespace App\Models;

use App\Enums\ActivityAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Append-only audit trail. Rows are never updated by the application;
 * `$guarded = ['*']` keeps them out of reach of mass assignment — the
 * ActivityLogger service is the only writer.
 */
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'activity_logs';

    /** Writes go through ActivityLogger::create() with explicit attributes only. */
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'is_security_event' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function actionEnum(): ?ActivityAction
    {
        return ActivityAction::tryFrom((string) $this->action);
    }

    public function getActionLabelAttribute(): string
    {
        return $this->actionEnum()?->label() ?? (string) $this->action;
    }

    // ------------------------------------------------------------- scopes

    public function scopeAction(Builder $query, string|ActivityAction $action): Builder
    {
        return $query->where('action', $action instanceof ActivityAction ? $action->value : $action);
    }

    public function scopeGroup(Builder $query, string $group): Builder
    {
        return $query->where('action_group', $group);
    }

    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeSecurityEvents(Builder $query): Builder
    {
        return $query->where('is_security_event', true);
    }

    public function scopeBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to));
    }

    public function scopeForSubject(Builder $query, Model $subject): Builder
    {
        return $query
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey());
    }
}
