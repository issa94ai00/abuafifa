<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\CatalogService;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function __construct(
        private readonly CatalogService $catalog,
        private readonly SeoService $seo
    ) {}

    public function index(Request $request)
    {
        $products = $this->catalog->products($request->only([
            'category', 'term', 'featured', 'is_new', 'offer',
            'min_price', 'max_price', 'availability', 'sort', 'per_page',
        ]));

        $categories = $this->catalog->categoriesTree();

        return Inertia::render('Public/Products/Index', [
            'products'  => $products,
            'categories'=> $categories,
            'seo'       => $this->seo->forPage('products'),
        ]);
    }

    public function show(string $slug)
    {
        $product = Product::query()
            ->with([
                'category:id,name_ar,name_en,slug',
                'cover',
                'images.media',
            ])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $product->increment('views_count');

        $related = $this->catalog->related($product, 4);

        return Inertia::render('Public/Products/Show', [
            'product' => $this->catalog->present($product),
            'product_full' => [
                'description'    => $product->description,
                'dimensions'     => $product->dimensions,
                'material'       => $product->material,
                'color'          => $product->color,
                'sku'            => $product->sku,
                'currency'       => $product->currency,
                'availability'   => $product->availability_status->label(),
                'gallery'        => $product->gallery,
                'variant_count'  => $product->variants()->where('is_active', true)->count(),
                'category'       => [
                    'name' => $product->category->name_ar,
                    'slug' => $product->category->slug,
                ],
            ],
            'related'   => $related,
            'seo'       => $this->seo->forModel($product),
        ]);
    }
}