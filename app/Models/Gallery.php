<?php

namespace App\Models;

use App\Enums\ActivityAction;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gallery extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @return array<string, ActivityAction>
     */
    protected static function activityActions(): array
    {
        return [
            'created' => ActivityAction::GalleryCreated,
            'updated' => ActivityAction::GalleryUpdated,
            'deleted' => ActivityAction::GalleryDeleted,
            'restored' => ActivityAction::GalleryRestored,
        ];
    }

    protected $fillable = [
        'title_ar',
        'title_en',
        'slug',
        'description_ar',
        'description_en',
        'cover_media_id',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function images(): HasMany
    {
        return $this->hasMany(GalleryImage::class)->orderBy('sort_order');
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function getDisplayTitleAttribute(): string
    {
        $locale = app()->getLocale();

        return $locale === 'ar' || ! $this->title_en
            ? $this->title_ar
            : $this->title_en;
    }

    public function getDisplayDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();

        return $locale === 'ar' || ! $this->description_en
            ? $this->description_ar
            : $this->description_en;
    }

    public function getCoverUrlAttribute(): ?string
    {
        return $this->cover?->url;
    }
}