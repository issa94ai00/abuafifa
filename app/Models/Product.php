<?php

namespace App\Models;

use App\Enums\ActivityAction;
use App\Models\Concerns\LogsActivity;
use App\Enums\AvailabilityStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @return array<string, ActivityAction>
     */
    protected static function activityActions(): array
    {
        return [
            'created' => ActivityAction::ProductCreated,
            'updated' => ActivityAction::ProductUpdated,
            'deleted' => ActivityAction::ProductDeleted,
            'restored' => ActivityAction::ProductRestored,
            'forceDeleted' => ActivityAction::ProductForceDeleted,
        ];
    }

    /**
     * @return list<string>
     */
    protected function auditExcept(): array
    {
        return ['created_at', 'updated_at', 'views_count'];
    }

    protected $fillable = [
        'category_id',
        'name_ar',
        'name_en',
        'slug',
        'sku',
        'short_description_ar',
        'short_description_en',
        'description_ar',
        'description_en',
        'cover_media_id',
        'price',
        'sale_price',
        'currency',
        'dimensions_ar',
        'dimensions_en',
        'material_ar',
        'material_en',
        'color_ar',
        'color_en',
        'availability_status',
        'is_featured',
        'is_new',
        'sort_order',
        'is_active',
        'views_count',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'is_featured' => 'boolean',
            'is_new' => 'boolean',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'views_count' => 'integer',
            'availability_status' => AvailabilityStatus::class,
        ];
    }

    // ---------------------------------------------------------- relationships

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function offers(): BelongsToMany
    {
        return $this->belongsToMany(Offer::class)
            ->withPivot(['price_override'])
            ->withTimestamps();
    }

    public function seo(): MorphOne
    {
        return $this->morphOne(SeoSetting::class, 'owner');
    }

    // --------------------------------------------------------------- scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('products.is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('products.is_featured', true);
    }

    public function scopeNewArrivals(Builder $query): Builder
    {
        return $query->where('products.is_new', true);
    }

    public function scopeOrderedBySortAndNewest(Builder $query): Builder
    {
        return $query->orderBy('products.sort_order')->orderByDesc('products.created_at');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('products.availability_status', '!=', AvailabilityStatus::SoldOut);
    }

    public function scopeInCategory(Builder $query, int|string $categoryId): Builder
    {
        return $query->where('products.category_id', $categoryId);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);

        return $query->where(function (Builder $q) use ($term) {
            $q->where('products.name_ar', 'like', "%{$term}%")
                ->orWhere('products.name_en', 'like', "%{$term}%")
                ->orWhere('products.sku', 'like', "%{$term}%")
                ->orWhere('products.short_description_ar', 'like', "%{$term}%")
                ->orWhere('products.short_description_en', 'like', "%{$term}%")
                ->orWhere('products.description_ar', 'like', "%{$term}%")
                ->orWhere('products.material_ar', 'like', "%{$term}%")
                ->orWhere('products.color_ar', 'like', "%{$term}%");
        });
    }

    // ----------------------------------------------------------- accessors

    public function getDisplayNameAttribute(): string
    {
        $locale = app()->getLocale();

        return $locale === 'ar' || ! $this->name_en
            ? $this->name_ar
            : $this->name_en;
    }

    public function getShortDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();

        return $locale === 'ar' || ! $this->short_description_en
            ? $this->short_description_ar
            : $this->short_description_en;
    }

    public function getDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();

        return $locale === 'ar' || ! $this->description_en
            ? $this->description_ar
            : $this->description_en;
    }

    public function getDimensionsAttribute(): ?string
    {
        $locale = app()->getLocale();

        return $locale === 'ar' || ! $this->dimensions_en
            ? $this->dimensions_ar
            : $this->dimensions_en;
    }

    public function getMaterialAttribute(): ?string
    {
        $locale = app()->getLocale();

        return $locale === 'ar' || ! $this->material_en
            ? $this->material_ar
            : $this->material_en;
    }

    public function getColorAttribute(): ?string
    {
        $locale = app()->getLocale();

        return $locale === 'ar' || ! $this->color_en
            ? $this->color_ar
            : $this->color_en;
    }

    public function getCoverUrlAttribute(): ?string
    {
        return $this->cover?->url;
    }

    public function getCoverThumbUrlAttribute(): ?string
    {
        return $this->cover?->thumb_url;
    }

    public function getGalleryAttribute(): array
    {
        return $this->images->map(fn (ProductImage $image) => [
            'id' => $image->id,
            'url' => $image->media?->url,
            'thumb' => $image->media?->thumb_url,
            'alt' => app()->getLocale() === 'ar'
                ? ($image->alt_ar ?? $image->media?->alt_ar)
                : ($image->alt_en ?? $image->media?->alt_en),
        ])->all();
    }

    /**
     * Eager-loadable counterpart of currentOffer().
     *
     * Listings load this once for the whole page; currentOffer() then
     * reads the loaded relation instead of querying per product, which is
     * what turned a 12-product grid into 24 extra round trips.
     */
    public function activeOffers(): BelongsToMany
    {
        return $this->offers()
            ->where('offers.is_active', true)
            ->where(fn ($q) => $q
                ->whereNull('offers.starts_at')->orWhere('offers.starts_at', '<=', now()))
            ->where(fn ($q) => $q
                ->whereNull('offers.ends_at')->orWhere('offers.ends_at', '>=', now()))
            ->orderBy('offers.sort_order');
    }

    /**
     * The best currently active offer on this product, if any.
     *
     * Uses the `activeOffers` relation when it has been eager loaded, and
     * falls back to a direct query for one-off calls.
     */
    public function currentOffer(): ?Offer
    {
        if ($this->relationLoaded('activeOffers')) {
            return $this->getRelation('activeOffers')->first();
        }

        return $this->activeOffers()->first();
    }

    /**
     * Final selling price after applying the active offer.
     */
    public function getEffectivePriceAttribute(): string
    {
        return number_format($this->effective_price_value, 2, '.', '');
    }

    public function getEffectivePriceValueAttribute(): float
    {
        return $this->priceWithOffer($this->currentOffer());
    }

    /**
     * Final price once a given offer is applied. Split out so a caller
     * that already resolved the offer does not resolve it again.
     */
    public function priceWithOffer(?Offer $offer): float
    {
        if (! $offer) {
            return (float) ($this->sale_price ?? $this->price);
        }

        return match ($offer->discount_type->value) {
            'custom_price' => (float) ($offer->pivot->price_override ?? $offer->price_override ?? $this->price),
            'percentage' => (float) min(
                $this->sale_price ?? $this->price,
                ($this->sale_price ?? $this->price) * (1 - ($offer->discount_value / 100))
            ),
            default => (float) max(0, ($this->sale_price ?? $this->price) - $offer->discount_value),
        };
    }

    protected function name(): Attribute
    {
        return Attribute::get(fn (): string => $this->display_name);
    }
}