<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public read-only endpoints
|--------------------------------------------------------------------------
| These serve published catalogue data only. Every query is scoped with
| active() so an unpublished product is not reachable by id, and the
| response is an explicit projection rather than a raw model, so a new
| column never becomes a new public field by accident.
*/

Route::middleware('throttle:api')->group(function () {

    Route::get('/products', function (Request $request) {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            // Bounded: an unbounded per_page is a cheap way to exhaust
            // memory on a public endpoint.
            'per_page' => ['nullable', 'integer', 'min:1', 'max:48'],
        ]);

        return Product::query()
            ->active()
            ->with(['category:id,name_ar,slug', 'cover'])
            ->when($validated['category_id'] ?? null, fn ($q, $id) => $q->where('category_id', $id))
            ->when($validated['search'] ?? null, function ($q, $search) {
                // Bound parameter — never concatenated into SQL.
                $q->where(fn ($w) => $w
                    ->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%"));
            })
            ->latest()
            ->paginate($validated['per_page'] ?? 12)
            ->through(fn (Product $product) => [
                'id' => $product->id,
                'name_ar' => $product->name_ar,
                'name_en' => $product->name_en,
                'slug' => $product->slug,
                'price' => $product->price,
                'sale_price' => $product->sale_price,
                'currency' => $product->currency,
                'availability_status' => $product->availability_status,
                'cover_url' => $product->cover?->url,
                'category' => $product->category?->only(['id', 'name_ar', 'slug']),
            ]);
    });

    Route::get('/categories', fn () => Category::active()
        ->withCount('products')
        ->get()
        ->map(fn (Category $category) => [
            'id' => $category->id,
            'name_ar' => $category->name_ar,
            'name_en' => $category->name_en,
            'slug' => $category->slug,
            'products_count' => $category->products_count,
        ]));
});

/*
|--------------------------------------------------------------------------
| Authenticated endpoints
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'active', 'throttle:api'])->group(function () {
    // A projection, not the model: returning $request->user() would ship
    // every non-hidden column to the client.
    Route::get('/user', fn (Request $request) => [
        'id' => $request->user()->id,
        'name' => $request->user()->name,
        'email' => $request->user()->email,
        'locale' => $request->user()->locale,
        'is_staff' => $request->user()->canAccessAdmin(),
    ]);
});
