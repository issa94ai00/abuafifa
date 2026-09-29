<?php

namespace App\Models;

use App\Enums\ActivityAction;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @return array<string, ActivityAction>
     */
    protected static function activityActions(): array
    {
        return [
            'created' => ActivityAction::CategoryCreated,
            'updated' => ActivityAction::CategoryUpdated,
            'deleted' => ActivityAction::CategoryDeleted,
            'restored' => ActivityAction::CategoryRestored,
        ];
    }

    protected $fillable = [
        'name_ar',
        'name_en',
        'slug',
        'description_ar',
        'description_en',
        'image_media_id',
        'parent_id',
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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_media_id');
    }

    public function seo(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(SeoSetting::class, 'owner');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name_ar');
    }

    /**
     * Number of products in this category.
     *
     * Prefers a value already loaded by withCount() — the accessor takes
     * precedence over the raw attribute during serialization, so without
     * this check a withCount() on a listing was silently discarded and
     * replaced by one COUNT query per row.
     */
    public function getProductsCountAttribute(): int
    {
        if (array_key_exists('products_count', $this->attributes)) {
            return (int) $this->attributes['products_count'];
        }

        return $this->is_active
            ? $this->products()->active()->count()
            : $this->products()->count();
    }

    public function getDisplayNameAttribute(): string
    {
        $locale = app()->getLocale();

        return $locale === 'ar' || ! $this->name_en
            ? $this->name_ar
            : $this->name_en;
    }

    protected function name(): Attribute
    {
        return Attribute::get(fn (): string => $this->display_name);
    }
}