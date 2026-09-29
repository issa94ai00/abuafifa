<?php

namespace App\Models;

use App\Enums\ActivityAction;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactMessage extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * Reading a message is noise; only deletions are audited.
     *
     * @return array<string, ActivityAction>
     */
    protected static function activityActions(): array
    {
        return [
            'deleted' => ActivityAction::MessageDeleted,
        ];
    }

    protected $fillable = [
        'name',
        'phone',
        'email',
        'subject',
        'message',
        'ip_address',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }
}