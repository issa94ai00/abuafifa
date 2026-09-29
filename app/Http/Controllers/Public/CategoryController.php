<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\CatalogService;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CatalogService $catalog,
        private readonly SeoService $seo
    ) {}

    public function index()
    {
        // A constrained eager load must select the keys the relation joins
        // on (id + parent_id) or the children never match their parent.
        $categories = Category::active()->ordered()
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->with([
                'image',
                'children' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')
                    ->select('id', 'parent_id', 'image_media_id', 'name_ar', 'name_en', 'slug', 'is_active', 'sort_order'),
            ])
            ->get();

        return Inertia::render('Public/Categories/Index', [
            'categories' => $categories,
            'seo'        => $this->seo->forPage('categories'),
        ]);
    }

    public function show(string $slug, Request $request)
    {
        $category = Category::active()
            ->with([
                'image',
                'children' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')
                    ->select('id', 'parent_id', 'image_media_id', 'name_ar', 'name_en', 'slug'),
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        $products = $this->catalog->products(
            array_merge($request->only(['sort', 'per_page']), ['category' => $category->id])
        );

        return Inertia::render('Public/Categories/Show', [
            'category'  => $category,
            'products'  => $products,
            'seo'       => $this->seo->forModel($category),
        ]);
    }
}