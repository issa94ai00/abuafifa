<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Offer;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class CatalogService
{
    /**
     * Relations every presented product needs.
     *
     * `activeOffers` is the important one: present() resolves the current
     * offer per product, and without eager loading that is one query per
     * card. `cover` covers the image URL.
     *
     * @var list<string>
     */
    private const PRODUCT_RELATIONS = [
        'category:id,name_ar,name_en,slug',
        'cover',
        'activeOffers',
    ];

    /**
     * The main product browsing query with filters & sorting.
     *
     * @param  array{category?:int|string, term?:?string, featured?:?bool, is_new?:?bool,
     *                  offer?:?bool, min_price?:?int|float, max_price?:?int|float,
     *                  availability?:?string, sort?:?string, per_page?:int}  $filters
     */
    public function products(array $filters = []): LengthAwarePaginator
    {
        $query = Product::query()
            ->with(self::PRODUCT_RELATIONS)
            ->active();

        // category (supports child categories' parent id)
        if (! empty($filters['category'])) {
            $categoryId = $filters['category'];

            $ids = is_numeric($categoryId)
                ? Category::query()
                    ->active()
                    ->where('id', $categoryId)
                    ->orWhere('parent_id', $categoryId)
                    ->pluck('id')
                    ->all()
                : Category::query()
                    ->active()
                    ->where('slug', $categoryId)
                    ->orWhereHas('parent', fn ($q) => $q->where('slug', $categoryId))
                    ->pluck('id')
                    ->all();

            $query->whereIn('products.category_id', $ids ?: [0]);
        }

        if (! empty($filters['term'])) {
            $query->search($filters['term']);
        }

        if (isset($filters['featured'])) {
            $query->when($filters['featured'], fn (Builder $q) => $q->featured());
        }

        if (isset($filters['is_new'])) {
            $query->when($filters['is_new'], fn (Builder $q) => $q->newArrivals());
        }

        if (isset($filters['offer']) && $filters['offer']) {
            $query->whereHas('offers', fn (Builder $q) => $q->active());
        }

        if (isset($filters['min_price'])) {
            $query->where('price', '>=', (float) $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('price', '<=', (float) $filters['max_price']);
        }

        if (! empty($filters['availability'])) {
            if ($filters['availability'] === 'available') {
                $query->available();
            } elseif ($filters['availability'] === 'sold_out') {
                $query->where('availability_status', 'sold_out');
            }
        }

        $sort = $filters['sort'] ?? 'latest';

        $query->when($sort, function (Builder $q) use ($sort) {
            match ($sort) {
                'price_asc'  => $q->orderBy('products.price'),
                'price_desc' => $q->orderByDesc('products.price'),
                'name'       => $q->orderBy('products.name_ar'),
                'popular'    => $q->orderByDesc('products.views_count'),
                default      => $q->orderByDesc('products.created_at'),
            };
        });

        $perPage = (int) ($filters['per_page'] ?? 12);

        return $query->paginate($perPage)
            ->withQueryString()
            ->through(fn (Product $p) => $this->present($p));
    }

    public function featured(int $limit = 8): array
    {
        return Product::query()
            ->with(self::PRODUCT_RELATIONS)
            ->active()
            ->featured()
            ->orderedBySortAndNewest()
            ->take($limit)
            ->get()
            ->map(fn (Product $p) => $this->present($p))
            ->all();
    }

    public function newArrivals(int $limit = 8): array
    {
        return Product::query()
            ->with(self::PRODUCT_RELATIONS)
            ->active()
            ->newArrivals()
            ->orderedBySortAndNewest()
            ->take($limit)
            ->get()
            ->map(fn (Product $p) => $this->present($p))
            ->all();
    }

    public function related(Product $product, int $limit = 4): array
    {
        return Product::query()
            ->with(self::PRODUCT_RELATIONS)
            ->active()
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->inRandomOrder()
            ->take($limit)
            ->get()
            ->map(fn (Product $p) => $this->present($p))
            ->all();
    }

    public function activeOffers(int $limit = 4): array
    {
        return Offer::query()
            ->with(['image'])
            ->active()
            ->orderBy('sort_order')
            ->take($limit)
            ->get()
            ->map(fn (Offer $o) => $this->presentOffer($o))
            ->all();
    }

    public function categoriesTree(): array
    {
        return Category::query()
            ->with(['image', 'children:id,name_ar,name_en,slug,parent_id,sort_order,is_active'])
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->active()
            ->whereNull('parent_id')
            ->ordered()
            ->get()
            ->map(fn (Category $c) => [
                'id'          => $c->id,
                'name_ar'     => $c->name_ar,
                'name_en'     => $c->name_en,
                'slug'        => $c->slug,
                'image_url'   => $c->image?->url,
                'image_thumb' => $c->image?->thumb_url,
                'products_count' => $c->products_count,
                'children'    => $c->children->where('is_active', true)->map(fn (Category $child) => [
                    'id'      => $child->id,
                    'name_ar' => $child->name_ar,
                    'name_en' => $child->name_en,
                    'slug'    => $child->slug,
                ])->values()->all(),
            ])->all();
    }

    // -------------------------------------------------------------- presentation

    public function present(Product $product): array
    {
        // One resolution, reused — priceWithOffer() takes the offer we
        // already have instead of looking it up a second time.
        $offer = $product->currentOffer();

        $basePrice = (float) ($product->sale_price ?? $product->price);
        $modalPrice = $offer ? $product->priceWithOffer($offer) : $basePrice;
        $hasOffer = $offer && $modalPrice < $basePrice;

        return [
            'id'             => $product->id,
            'name'           => $product->display_name,
            'name_ar'        => $product->name_ar,
            'name_en'        => $product->name_en,
            'slug'           => $product->slug,
            'sku'            => $product->sku,
            'short_description' => $product->short_description,
            'price'          => (float) $product->price,
            'sale_price'     => $product->sale_price !== null ? (float) $product->sale_price : null,
            'effective_price'=> $modalPrice,
            'has_offer'      => $hasOffer,
            'discount_pct'   => $basePrice > 0 ? (int) round(($basePrice - $modalPrice) / $basePrice * 100) : 0,
            'currency'       => $product->currency,
            'availability'   => $product->availability_status->value,
            'availability_label' => $product->availability_status->label(),
            'is_featured'    => $product->is_featured,
            'is_new'         => $product->is_new,
            'views_count'    => $product->views_count,
            'dimensions'     => $product->dimensions,
            'material'       => $product->material,
            'color'          => $product->color,
            'cover_url'      => $product->cover_url,
            'cover_thumb_url'=> $product->cover_thumb_url,
            'category'       => $product->category ? [
                'id'   => $product->category->id,
                'name' => $product->category->name_ar,
                'slug' => $product->category->slug,
            ] : null,
        ];
    }

    public function presentOffer(Offer $offer): array
    {
        return [
            'id'          => $offer->id,
            'title'       => $offer->display_title,
            'title_ar'    => $offer->title_ar,
            'slug'        => $offer->slug,
            'description' => $offer->display_description,
            'image_url'   => $offer->image_url,
            'discount_type' => $offer->discount_type->value,
            'discount_value' => $offer->discount_value !== null ? (float) $offer->discount_value : null,
            'badge'       => $offer->badge_label,
            'starts_at'   => $offer->starts_at?->toIso8601String(),
            'ends_at'     => $offer->ends_at?->toIso8601String(),
            'is_active'   => $offer->is_active,
        ];
    }
}