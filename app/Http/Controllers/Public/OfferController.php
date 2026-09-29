<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Services\SeoService;
use Inertia\Inertia;

class OfferController extends Controller
{
    public function __construct(
        private readonly SeoService $seo
    ) {}

    public function index()
    {
        $offers = Offer::active()
            ->with(['image', 'products' => fn ($q) => $q->where('products.is_active', true)->with('cover')])
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Public/Offers/Index', [
            'offers' => $offers->map(fn (Offer $o) => [
                'id'          => $o->id,
                'title'       => $o->display_title,
                'slug'        => $o->slug,
                'description' => $o->display_description,
                'image_url'   => $o->image_url,
                'badge'       => $o->badge_label,
                'starts_at'   => $o->starts_at?->toIso8601String(),
                'ends_at'     => $o->ends_at?->toIso8601String(),
                'products'    => $o->products->take(6)->map(fn ($p) => [
                    'id'   => $p->id,
                    'name' => $p->display_name,
                    'slug' => $p->slug,
                    'price'=> $p->price,
                    'cover'=> $p->cover_url,
                ]),
            ]),
            'seo' => $this->seo->forPage('offers'),
        ]);
    }

    public function show(string $slug)
    {
        $offer = Offer::active()
            ->with([
                'image',
                'products' => fn ($q) => $q->where('products.is_active', true),
                'products.cover',
                'products.category:id,name_ar,slug',
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        return Inertia::render('Public/Offers/Show', [
            'offer' => [
                'id'          => $offer->id,
                'title'       => $offer->display_title,
                'description' => $offer->display_description,
                'image_url'   => $offer->image_url,
                'badge'       => $offer->badge_label,
                'starts_at'   => $offer->starts_at?->toIso8601String(),
                'ends_at'     => $offer->ends_at?->toIso8601String(),
                'products'    => $offer->products->map(fn ($p) => [
                    'id'     => $p->id,
                    'name'   => $p->display_name,
                    'slug'   => $p->slug,
                    'price'  => $p->price,
                    'cover'  => $p->cover_url,
                ]),
            ],
            'seo' => $this->seo->forModel($offer),
        ]);
    }
}