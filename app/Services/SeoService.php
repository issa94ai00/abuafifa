<?php

namespace App\Services;

use App\Models\SeoSetting;
use Illuminate\Database\Eloquent\Model;

class SeoService
{
    /**
     * Resolve SEO metadata for a static route (home/contact/about...).
     */
    public function forPage(string $routeName): array
    {
        $seo = SeoSetting::query()->where('route_name', $routeName)->first();
        $defaults = $this->defaults();

        $title = $seo?->meta_title
            ?? ($routeName === 'home' ? $defaults['title'] : $defaults['title'].' — '.$defaults['site_name']);

        return [
            'title'           => $title,
            'description'     => $seo?->meta_description ?? $defaults['description'],
            'keywords'        => $seo?->keywords ?? null,
            'og_title'        => $seo?->og_title ?? $title,
            'og_description'  => $seo?->og_description ?? $seo?->meta_description,
            'og_image'        => $seo?->ogImage?->url ?? $defaults['og_image'],
            'canonical'       => $seo?->canonical_url ?? url()->current(),
            'robots'          => $seo?->robots ?? 'index,follow',
        ];
    }

    /**
     * Resolve SEO metadata for a model (product/category/offer).
     */
    public function forModel(Model $model): array
    {
        $seo = $model->relationLoaded('seo')
            ? $model->seo
            : $model->seo()->first();

        // display_name / cover_url are *accessors*, not methods, so
        // method_exists() was always false here — every model page got the
        // fully-qualified class name as its SEO title.
        $name = (string) ($model->display_name
            ?? $model->display_title
            ?? $model->name
            ?? class_basename($model));

        $defaults = $this->defaults();

        $title = $seo?->meta_title
            ?? ($name.' — '.$defaults['site_name']);

        $description = $seo?->meta_description
            ?? ($name.' — '.$defaults['description']);

        $image = $seo?->ogImage?->url
            ?? $model->cover_url
            ?? $model->image_url
            ?? $defaults['og_image'];

        return [
            'title'          => $title,
            'description'    => $description,
            'keywords'       => $seo?->keywords ?? null,
            'og_title'       => $seo?->og_title ?? $title,
            'og_description' => $seo?->og_description ?? $description,
            'og_image'       => $image,
            'canonical'      => $seo?->canonical_url ?? url()->current(),
            'robots'         => $seo?->robots ?? 'index,follow',
        ];
    }

    /**
     * Global defaults from the settings store.
     */
    public function defaults(): array
    {
        return [
            'site_name'   => setting('site.name', setting('site.title', 'معرض أبو عفيفة للمفروشات')),
            'title'       => setting('seo.default_title', setting('site.title', 'معرض أبو عفيفة للمفروشات')),
            'description' => setting('seo.default_description', 'معرض أبو عفيفة للمفروشات — صالة عرض أثاث وطقم صالونات وغرف نوم فاخرة بأرقى الخامات والتصاميم العصرية.'),
            'og_image'    => setting('seo.default_og_image',
                asset('img/og-default.jpg')),
        ];
    }
}