<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DiscountType;
use App\Enums\MediaCollection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOfferRequest;
use App\Http\Requests\Admin\UpdateOfferRequest;
use App\Models\Offer;
use App\Models\Product;
use App\Services\MediaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;

class OfferController extends Controller
{
    /**
     * Sort keys the index accepts, mapped to the ordering they apply.
     * Anything else falls back to `latest`.
     */
    private const SORTS = ['latest', 'oldest', 'title', 'order', 'ending'];

    private const PER_PAGE = [12, 24, 48, 96];

    public function __construct(
        private readonly MediaService $mediaService
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Offer::class);

        $trash = $request->boolean('trash');
        $state = $request->string('state')->toString();
        $sort = in_array($request->input('sort'), self::SORTS, true)
            ? $request->input('sort')
            : 'latest';

        $perPage = (int) $request->integer('per_page', 12);
        $perPage = in_array($perPage, self::PER_PAGE, true) ? $perPage : 12;

        $offers = Offer::query()
            ->withCount('products')
            ->with(['image', 'products:id'])
            ->when($trash, fn ($q) => $q->onlyTrashed())
            ->when($request->filled('search'), function (Builder $q) use ($request) {
                $search = '%'.trim($request->string('search')).'%';

                $q->where(fn (Builder $q) => $q
                    ->where('title_ar', 'like', $search)
                    ->orWhere('title_en', 'like', $search)
                    ->orWhere('slug', 'like', $search));
            })
            ->when(! $trash && $state !== '', fn (Builder $q) => $this->applyState($q, $state))
            ->tap(fn (Builder $q) => $this->applySort($q, $sort))
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Offer $offer) => $this->presentRow($offer));

        return Inertia::render('Admin/Offers/Index', [
            'offers'   => $offers,
            'stats'    => $this->stats(),
            'products' => $this->productOptions(),
            'currency' => (new Offer)->currency_symbol,
            'filters'  => [
                'search'   => $request->string('search')->toString() ?: '',
                'state'    => $state,
                'trash'    => $trash,
                'sort'     => $sort,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function show(Offer $offer)
    {
        Gate::authorize('view', $offer);

        $offer->load([
            'image',
            'products' => fn ($q) => $q->with('cover')->orderBy('name_ar'),
            'seo',
        ]);

        return Inertia::render('Admin/Offers/Show', [
            'offer' => $this->presentRow($offer),
            // Only the products actually attached to this offer — the
            // page renders this list as "linked products".
            'products' => $offer->products->map(fn (Product $product) => [
                'id'              => $product->id,
                'name_ar'         => $product->name_ar,
                'name_en'         => $product->name_en,
                'slug'            => $product->slug,
                'price'           => $product->price !== null ? (float) $product->price : null,
                'cover_thumb_url' => $product->cover?->thumb_url,
                'is_active'       => (bool) $product->is_active,
            ])->values(),
            'allProducts' => $this->productOptions(),
            'currency'    => (new Offer)->currency_symbol,
        ]);
    }

    public function store(StoreOfferRequest $request)
    {
        Gate::authorize('create', Offer::class);

        $data = $request->validated();

        $offer = DB::transaction(function () use ($request, $data) {
            $media = $request->hasFile('image')
                ? $this->mediaService->storeOne($request->file('image'), MediaCollection::Offer->value)
                : null;

            try {
                $offer = Offer::create($this->attributesFrom($data) + [
                    'slug' => $this->resolveSlug($data['slug'] ?? null, $data['title_ar']),
                    'image_media_id' => $media?->getKey(),
                ]);
            } catch (\Throwable $e) {
                // The rollback undoes the Media row but not the file it
                // already wrote to disk, so that is cleaned up by hand.
                $media && $this->mediaService->delete($media);

                throw $e;
            }

            $this->syncProducts($offer, $request, $data);

            return $offer;
        });

        return back()->with('success', "تم إضافة العرض «{$offer->title_ar}» بنجاح.");
    }

    public function update(UpdateOfferRequest $request, Offer $offer)
    {
        Gate::authorize('update', $offer);

        $data = $request->validated();

        DB::transaction(function () use ($request, $data, $offer) {
            $offer->loadMissing('image');
            $previous = $offer->image;

            $attributes = $this->attributesFrom($data) + [
                'slug' => $this->resolveSlug($data['slug'] ?? null, $data['title_ar'], $offer),
            ];

            // Swapping or clearing the banner used to leave the old Media
            // row — and its two files — with nothing pointing at them.
            $releasePrevious = false;

            if ($request->hasFile('image')) {
                $attributes['image_media_id'] = $this->mediaService
                    ->storeOne($request->file('image'), MediaCollection::Offer->value)
                    ->getKey();
                $releasePrevious = true;
            } elseif ($request->boolean('remove_image')) {
                $attributes['image_media_id'] = null;
                $releasePrevious = true;
            }

            $offer->update($attributes);

            // Only after the column no longer points at it, or
            // deleteIfUnused() would still count this offer as a user.
            if ($releasePrevious && $previous) {
                $this->mediaService->deleteIfUnused($previous);
            }

            $this->syncProducts($offer, $request, $data);
        });

        return back()->with('success', "تم تحديث العرض «{$offer->title_ar}» بنجاح.");
    }

    /**
     * Flip visibility without opening the full edit form — the single
     * field admins toggle most often from the list.
     */
    public function toggleActive(Offer $offer)
    {
        Gate::authorize('update', $offer);

        $offer->update(['is_active' => ! $offer->is_active]);

        return back()->with('success', $offer->is_active
            ? "تم إظهار العرض «{$offer->title_ar}»."
            : "تم إخفاء العرض «{$offer->title_ar}».");
    }

    public function destroy(Offer $offer)
    {
        Gate::authorize('delete', $offer);

        $offer->delete();

        return back()->with('success', "تم نقل العرض «{$offer->title_ar}» إلى سلة المحذوفات.");
    }

    public function restore(Offer $offer)
    {
        Gate::authorize('restore', $offer);

        $offer->restore();

        return back()->with('success', "تم استعادة العرض «{$offer->title_ar}» بنجاح.");
    }

    // --------------------------------------------------------------- writing

    /**
     * The column values shared by store and update. `slug` is resolved
     * separately because the two differ on what "keep the old one" means.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributesFrom(array $data): array
    {
        $type = DiscountType::from($data['discount_type']);

        return [
            'title_ar'       => $data['title_ar'],
            'title_en'       => $data['title_en'] ?? null,
            'description_ar' => $data['description_ar'] ?? null,
            'description_en' => $data['description_en'] ?? null,
            'discount_type'  => $type,
            // A percentage/fixed offer has no custom price and a custom
            // price offer has no discount value; clearing the unused one
            // keeps a row from describing two different discounts at once.
            'discount_value' => $type === DiscountType::CustomPrice ? null : ($data['discount_value'] ?? null),
            'price_override' => $type === DiscountType::CustomPrice ? ($data['price_override'] ?? null) : null,
            'starts_at'      => $data['starts_at'] ?? null,
            'ends_at'        => $data['ends_at'] ?? null,
            'sort_order'     => $data['sort_order'] ?? 0,
            'is_active'      => $data['is_active'] ?? true,
        ];
    }

    /**
     * Auto-generated slugs are suffixed until unique — two offers may
     * legitimately share a title, but the column is unique.
     */
    private function resolveSlug(?string $provided, string $titleAr, ?Offer $offer = null): string
    {
        if (filled($provided)) {
            return $provided;
        }

        if ($offer && filled($offer->slug)) {
            return $offer->slug;
        }

        $base = Str::slug($titleAr) ?: 'offer';
        $slug = $base;
        $suffix = 2;

        while (Offer::withTrashed()
            ->where('slug', $slug)
            ->when($offer, fn ($q) => $q->whereKeyNot($offer->getKey()))
            ->exists()
        ) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * An empty product list is dropped entirely by multipart encoding, so
     * the form sends an explicit `sync_products` flag to say "this payload
     * carries the full list" — without it, detaching the last product
     * would silently do nothing.
     *
     * @param  array<string, mixed>  $data
     */
    private function syncProducts(Offer $offer, Request $request, array $data): void
    {
        if (! $request->boolean('sync_products') && ! array_key_exists('products', $data)) {
            return;
        }

        $offer->products()->sync($data['products'] ?? []);
    }

    // --------------------------------------------------------------- reading

    private function applyState(Builder $query, string $state): void
    {
        match ($state) {
            'active' => $query->active(),
            'upcoming' => $query->where('is_active', true)->upcoming(),
            'ended' => $query->where('is_active', true)
                ->whereNotNull('ends_at')->where('ends_at', '<', now()),
            'hidden' => $query->where('is_active', false),
            default => null,
        };
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'oldest' => $query->oldest(),
            'title' => $query->orderBy('title_ar'),
            'order' => $query->orderBy('sort_order')->latest(),
            // Offers with no end date sort last rather than first.
            'ending' => $query->orderByRaw('ends_at is null, ends_at asc'),
            default => $query->latest(),
        };
    }

    /**
     * The admin page reads accessors (`badge_label`, image URLs) that are
     * not part of the model's serialised attributes, so every row is
     * presented explicitly rather than handed over as a raw model.
     *
     * @return array<string, mixed>
     */
    private function presentRow(Offer $offer): array
    {
        $type = $offer->discount_type;

        return [
            'id'                  => $offer->id,
            'title_ar'            => $offer->title_ar,
            'title_en'            => $offer->title_en,
            'slug'                => $offer->slug,
            'description_ar'      => $offer->description_ar,
            'description_en'      => $offer->description_en,
            'discount_type'       => $type?->value,
            'discount_type_label' => $type?->label(),
            'discount_value'      => $offer->discount_value !== null ? (float) $offer->discount_value : null,
            'price_override'      => $offer->price_override !== null ? (float) $offer->price_override : null,
            'badge_label'         => $offer->badge_label,
            'currency_symbol'     => $offer->currency_symbol,
            'starts_at'           => $offer->starts_at?->toIso8601String(),
            'ends_at'             => $offer->ends_at?->toIso8601String(),
            'sort_order'          => (int) $offer->sort_order,
            'is_active'           => (bool) $offer->is_active,
            'image_url'           => $offer->image?->url,
            'image_thumb_url'     => $offer->image?->thumb_url,
            'products_count'      => (int) ($offer->products_count ?? $offer->products->count()),
            'product_ids'         => $offer->relationLoaded('products')
                ? $offer->products->pluck('id')->values()
                : [],
            'state'      => $this->stateOf($offer),
            'trashed'    => $offer->trashed(),
            'deleted_at' => $offer->deleted_at?->toIso8601String(),
            'created_at' => $offer->created_at?->toIso8601String(),
        ];
    }

    /**
     * Derived on the server so the list, the filters and the counters all
     * agree on what "active" means.
     */
    private function stateOf(Offer $offer): string
    {
        if (! $offer->is_active) {
            return 'hidden';
        }

        if ($offer->starts_at && $offer->starts_at->isFuture()) {
            return 'upcoming';
        }

        if ($offer->ends_at && $offer->ends_at->isPast()) {
            return 'ended';
        }

        return 'active';
    }

    /**
     * @return array<string, int>
     */
    private function stats(): array
    {
        // Six separate COUNT(*) scans became one pass with conditional
        // aggregates — the counters render on every index request.
        $now = now();

        $row = Offer::query()
            ->withTrashed()
            ->selectRaw('count(case when deleted_at is null then 1 end) as total')
            ->selectRaw(
                'count(case when deleted_at is null and is_active = 1'
                .' and (starts_at is null or starts_at <= ?)'
                .' and (ends_at is null or ends_at >= ?) then 1 end) as active',
                [$now, $now]
            )
            ->selectRaw(
                'count(case when deleted_at is null and is_active = 1'
                .' and starts_at is not null and starts_at > ? then 1 end) as upcoming',
                [$now]
            )
            ->selectRaw(
                'count(case when deleted_at is null and is_active = 1'
                .' and ends_at is not null and ends_at < ? then 1 end) as ended',
                [$now]
            )
            ->selectRaw('count(case when deleted_at is null and is_active = 0 then 1 end) as hidden')
            ->selectRaw('count(case when deleted_at is not null then 1 end) as trashed')
            ->first();

        return [
            'total'    => (int) ($row->total ?? 0),
            'active'   => (int) ($row->active ?? 0),
            'upcoming' => (int) ($row->upcoming ?? 0),
            'ended'    => (int) ($row->ended ?? 0),
            'hidden'   => (int) ($row->hidden ?? 0),
            'trashed'  => (int) ($row->trashed ?? 0),
        ];
    }

    /**
     * Products offered in the attach picker. Small enough (catalogue
     * scale) to ship in full and filter client-side.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function productOptions()
    {
        return Product::query()
            ->with('cover')
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'name_en', 'slug', 'price', 'is_active', 'cover_media_id'])
            ->map(fn (Product $product) => [
                'id'              => $product->id,
                'name_ar'         => $product->name_ar,
                'name_en'         => $product->name_en,
                'slug'            => $product->slug,
                'price'           => $product->price !== null ? (float) $product->price : null,
                'is_active'       => (bool) $product->is_active,
                'cover_thumb_url' => $product->cover?->thumb_url,
            ])
            ->values();
    }
}
