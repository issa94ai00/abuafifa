<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\ToggleFavoriteRequest;
use App\Models\Product;
use App\Services\CatalogService;

class FavoriteController extends Controller
{
    public function __construct(
        private readonly CatalogService $catalog
    ) {}

    public function toggle(ToggleFavoriteRequest $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $favorite = $user->favorites()->where('product_id', $request->integer('product_id'))->first();

        if ($favorite) {
            $favorite->delete();

            return back()->with('success', 'تمت إزالة المنتج من المفضلة.');
        }

        $user->favorites()->create(['product_id' => $request->integer('product_id')]);

        return back()->with('success', 'تمت إضافة المنتج إلى المفضلة.');
    }

    public function index()
    {
        $products = Product::query()
            ->whereHas('favorites', fn ($q) => $q->where('user_id', auth()->id()))
            ->active()
            ->with('cover', 'category:id,name_ar,slug')
            ->latest()
            ->paginate(18);

        return \Inertia\Inertia::render('Public/Favorites', [
            'products' => $products->through(fn (Product $p) => $this->catalog->present($p)),
        ]);
    }
}