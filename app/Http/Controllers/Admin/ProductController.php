<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function __construct(
        private readonly MediaService $mediaService
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Product::class);

        $query = Product::query()
            ->with(['category:id,name_ar,name_en,slug', 'cover'])
            ->withCount('images')
            ->withTrashed()
            ->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                  ->orWhere('name_en', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('availability')) {
            $query->where('availability_status', $request->input('availability'));
        }

        $trash = $request->boolean('trash');
        if ($trash) {
            $query->onlyTrashed();
        }

        $products = $query->paginate($request->integer('per_page', 15))
            ->withQueryString()
            ->through(fn (Product $product) => $this->presentRow($product, $trash));

        $categories = Category::active()->ordered()
            ->get(['id', 'name_ar', 'name_en', 'slug']);

        return Inertia::render('Admin/Products/Index', [
            'products'   => $products,
            'categories' => $categories,
            'filters'    => $request->only(['search', 'category_id', 'trash', 'availability']),
            'stats'      => $this->stats(),
        ]);
    }

    public function create()
    {
        Gate::authorize('create', Product::class);

        return Inertia::render('Admin/Products/Form', [
            'categories' => Category::active()->ordered()->get(['id', 'name_ar', 'slug']),
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        // The form request already authorised 'create'; this repeats the
        // check so the action stays safe if the request class is swapped.
        Gate::authorize('create', Product::class);

        $data = $request->validated();

        DB::beginTransaction();

        try {
            $product = Product::create([
                'category_id'          => $data['category_id'],
                'name_ar'              => $data['name_ar'],
                'name_en'              => $data['name_en'] ?? null,
                'slug'                 => $data['slug'] ?? Str::slug($data['name_ar']),
                'sku'                  => $data['sku'] ?? null,
                'short_description_ar' => $data['short_description_ar'] ?? null,
                'short_description_en' => $data['short_description_en'] ?? null,
                'description_ar'       => $data['description_ar'] ?? null,
                'description_en'       => $data['description_en'] ?? null,
                'price'                => $data['price'],
                'sale_price'           => $data['sale_price'] ?? null,
                'currency'             => $data['currency'] ?? 'JOD',
                'dimensions_ar'        => $data['dimensions_ar'] ?? null,
                'dimensions_en'        => $data['dimensions_en'] ?? null,
                'material_ar'          => $data['material_ar'] ?? null,
                'material_en'          => $data['material_en'] ?? null,
                'color_ar'             => $data['color_ar'] ?? null,
                'color_en'             => $data['color_en'] ?? null,
                'availability_status'  => $data['availability_status'] ?? 'available',
                'is_featured'          => $data['is_featured'] ?? false,
                'is_new'               => $data['is_new'] ?? false,
                'is_active'            => $data['is_active'] ?? true,
                'sort_order'           => $data['sort_order'] ?? 0,
            ]);

            if ($request->hasFile('cover_image')) {
                $media = $this->mediaService->storeOne($request->file('cover_image'), 'product-covers');
                $product->update(['cover_media_id' => $media->id]);
            }

            $this->attachGalleryImages($product, $request);

            DB::commit();

            return redirect()->route('admin.products.index')
                ->with('success', 'تم إضافة المنتج بنجاح.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Product store failed: {$e->getMessage()}", ['trace' => $e->getTraceAsString()]);

            return back()->withInput()->withErrors(['error' => 'حدث خطأ أثناء حفظ المنتج.']);
        }
    }

    public function show(Product $product)
    {
        Gate::authorize('view', $product);

        $product->load(['category:id,name_ar,name_en,slug', 'cover', 'images.media', 'offers', 'seo']);

        return Inertia::render('Admin/Products/Show', [
            'product' => [
                'id'         => $product->id,
                'name_ar'    => $product->name_ar,
                'name_en'    => $product->name_en,
                'slug'       => $product->slug,
                'sku'        => $product->sku,
                'price'      => $product->price,
                'sale_price' => $product->sale_price,
                'currency'   => $product->currency,
                'availability_status' => $product->availability_status->value,
                'is_featured'=> $product->is_featured,
                'is_new'     => $product->is_new,
                'is_active'  => $product->is_active,
                'views_count'=> $product->views_count,
                'category'   => $product->category?->name_ar,
                'cover_url'  => $product->cover?->url,
                'gallery'    => $product->images->map(fn ($i) => [
                    'id'  => $i->id,
                    'url' => $i->media?->url,
                ]),
                'created_at' => $product->created_at->toDateTimeString(),
                'updated_at' => $product->updated_at->toDateTimeString(),
            ],
        ]);
    }

    public function edit(Product $product)
    {
        Gate::authorize('update', $product);

        $product->load(['cover', 'images.media']);

        return Inertia::render('Admin/Products/Form', [
            'product'    => $product,
            'cover_url'  => $product->cover?->url,
            'categories' => Category::active()->ordered()->get(['id', 'name_ar', 'slug']),
            'existingImages' => $product->images->map(fn ($img) => [
                'id'   => $img->id,
                'url'  => $img->media?->url,
                'alt'  => $img->alt_ar,
                'sort' => $img->sort_order,
            ]),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        Gate::authorize('update', $product);

        $data = $request->validated();

        DB::beginTransaction();

        try {
            $product->update(
                collect($data)->except(['cover_image', 'gallery_images', 'remove_image_ids'])->toArray()
            );

            if ($request->hasFile('cover_image')) {
                $media = $this->mediaService->storeOne($request->file('cover_image'), 'product-covers');
                $product->update(['cover_media_id' => $media->id]);
            }

            if (! empty($data['remove_image_ids'])) {
                $product->images()
                    ->whereIn('id', $data['remove_image_ids'])
                    ->delete();
            }

            $this->attachGalleryImages($product, $request);

            DB::commit();

            return redirect()->route('admin.products.index')
                ->with('success', 'تم تحديث المنتج بنجاح.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Product update failed: {$e->getMessage()}");

            return back()->withInput()->withErrors(['error' => 'حدث خطأ أثناء تحديث المنتج.']);
        }
    }

    public function destroy(Product $product)
    {
        Gate::authorize('delete', $product);

        $product->delete();

        return back()->with('success', 'تم نقل المنتج إلى سلة المحذوفات.');
    }

    /**
     * The route binds with `withTrashed()`, so the model arrives here
     * already resolved instead of being looked up from a raw id.
     */
    public function restore(Product $product)
    {
        Gate::authorize('restore', $product);

        $product->restore();

        return back()->with('success', 'تم استعادة المنتج بنجاح.');
    }

    public function forceDelete(Product $product)
    {
        Gate::authorize('forceDelete', $product);

        // LogsActivity records the permanent deletion, including a
        // snapshot of the row that is about to disappear.
        $product->forceDelete();

        return back()->with('success', 'تم حذف المنتج نهائياً.');
    }

    // --------------------------------------------------------------- helpers

    private function presentRow(Product $product, bool $trashedMode): array
    {
        return [
            'id'                  => $product->id,
            'name_ar'             => $product->name_ar,
            'name_en'             => $product->name_en,
            'slug'                => $product->slug,
            'sku'                 => $product->sku,
            'price'               => (float) $product->price,
            'sale_price'          => $product->sale_price !== null ? (float) $product->sale_price : null,
            'currency'            => $product->currency,
            'availability_status' => $product->availability_status->value,
            'availability_label'  => $product->availability_status->label(),
            'is_featured'         => (bool) $product->is_featured,
            'is_new'              => (bool) $product->is_new,
            'is_active'           => (bool) $product->is_active,
            'views_count'         => $product->views_count,
            'images_count'        => $product->images_count,
            'category'            => $product->category
                ? ['id' => $product->category->id, 'name_ar' => $product->category->name_ar]
                : null,
            'cover_url'           => $product->cover?->url,
            'cover_thumb_url'     => $product->cover?->thumb_url,
            'trashed'             => $trashedMode || $product->trashed(),
            'created_at'          => $product->created_at?->toDateTimeString(),
            'deleted_at'          => $product->deleted_at?->toDateTimeString(),
        ];
    }

    private function stats(): array
    {
        return [
            'total'    => Product::withTrashed()->count(),
            'active'   => Product::active()->count(),
            'inactive' => Product::withTrashed()->where('is_active', false)->count(),
            'featured' => Product::featured()->count(),
            'trashed'  => Product::onlyTrashed()->count(),
        ];
    }

    private function attachGalleryImages(Product $product, Request $request): void
    {
        if (! $request->hasFile('gallery_images')) {
            return;
        }

        $existingCount = $product->images()->count();

        $mediaCollection = $this->mediaService->storeUploads(
            $request->file('gallery_images'),
            'product-gallery'
        );

        $mediaCollection->each(function ($media, $index) use ($product, $existingCount) {
            $product->images()->create([
                'media_id'   => $media->id,
                'sort_order' => $existingCount + $index,
                'alt_ar'     => $media->alt_ar,
                'alt_en'     => $media->alt_en,
            ]);
        });
    }
}