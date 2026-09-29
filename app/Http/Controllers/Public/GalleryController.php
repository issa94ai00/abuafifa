<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use App\Services\SeoService;
use Inertia\Inertia;

class GalleryController extends Controller
{
    public function __construct(
        private readonly SeoService $seo
    ) {}

    public function index()
    {
        $groups = Gallery::active()
            ->with(['images.media'])
            ->get();

        return Inertia::render('Public/Gallery', [
            'groups' => $groups->map(fn (Gallery $g) => [
                'id'          => $g->id,
                'title'       => $g->display_title,
                'slug'        => $g->slug,
                'description' => $g->display_description,
                'cover'       => $g->cover_url,
                'images'      => $g->images->map(fn ($img) => [
                    'id'      => $img->id,
                    'url'     => $img->media?->url,
                    'thumb'   => $img->media?->thumb_url,
                    'width'   => $img->media?->width,
                    'height'  => $img->media?->height,
                    'caption' => $img->caption,
                ]),
            ]),
            'seo' => $this->seo->forPage('gallery'),
        ]);
    }
}