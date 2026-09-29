<?php

namespace App\Models;

use App\Enums\ActivityAction;
use App\Models\Concerns\LogsActivity;
use App\Enums\DiscountType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Route;

class Offer extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @return array<string, ActivityAction>
     */
    protected static function activityActions(): array
    {
        return [
            'created' => ActivityAction::OfferCreated,
            'updated' => ActivityAction::OfferUpdated,
            'deleted' => ActivityAction::OfferDeleted,
            'restored' => ActivityAction::OfferRestored,
        ];
    }

    protected $fillable = [
        'title_ar',
        'title_en',
        'slug',
        'description_ar',
        'description_en',
        'image_media_id',
        'discount_type',
        'discount_value',
        'price_override',
        'starts_at',
        'ends_at',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_type' => DiscountType::class,
            'discount_value' => 'decimal:2',
            'price_override' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_media_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot(['price_override'])
            ->withTimestamps();
    }

    public function seo(): MorphOne
    {
        return $this->morphOne(SeoSetting::class, 'owner');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(fn ($q) => $q
                ->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q
                ->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereNotNull('starts_at')->where('starts_at', '>', now());
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

    public function getImageUrlAttribute(): ?string
    {
        return $this->image?->url;
    }

    public function getBadgeLabelAttribute(): string
    {
        return match ($this->discount_type) {
            DiscountType::Percentage => '-'.rtrim(rtrim(number_format((float) $this->discount_value, 2, '.', ''), '0'), '.').'٪',
            DiscountType::Fixed => 'خصم '.number_format((float) $this->discount_value, 0).' '.$this->currency_symbol,
            DiscountType::CustomPrice => 'سعر خاص',
        };
    }

    public function getCurrencySymbolAttribute(): string
    {
        return match (setting('site.currency', 'JOD')) {
            'USD' => '$',
            'EUR' => '€',
            'SYP' => 'ل.س',
            default => 'د.أ',
        };
    }

    public function isCurrentlyActive(): bool
    {
        $insideWindow = (! $this->starts_at || $this->starts_at->lte(now()))
            && (! $this->ends_at || $this->ends_at->gte(now()));

        return $this->is_active && $insideWindow;
    }
}