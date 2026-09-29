<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Gallery;
use App\Models\Product;
use App\Services\CatalogService;
use App\Services\SeoService;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function __construct(
        private readonly CatalogService $catalog,
        private readonly SeoService $seo
    ) {}

    public function __invoke()
    {
        return Inertia::render('Public/Home', [
            'featured'    => $this->catalog->featured(8),
            'newArrivals' => $this->catalog->newArrivals(8),
            'offers'      => $this->catalog->activeOffers(3),
            'categories'  => $this->catalog->categoriesTree(),
            'galleries'   => Gallery::active()
                ->with('cover')
                ->withCount('images')
                ->take(4)
                ->get()
                ->map(fn (Gallery $g) => [
                    'id'    => $g->id,
                    'title' => $g->display_title,
                    'slug'  => $g->slug,
                    'cover' => $g->cover_url,
                    'images_count' => $g->images_count,
                ]),
            'seo' => $this->seo->forPage('home'),
        ]);
    }
}