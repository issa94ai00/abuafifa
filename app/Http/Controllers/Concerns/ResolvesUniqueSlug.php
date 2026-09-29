<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Derives a URL slug from a title and guarantees it is free.
 *
 * Auto-generated slugs collide more often than they look like they
 * would: two galleries called «معرض الصور» transliterate to the same
 * string, and the column is unique, so the second save died with an
 * integrity-constraint 500 instead of a form error. Titles that carry no
 * transliterable characters at all (emoji, punctuation) slugify to an
 * empty string, which collides with every other such title.
 */
trait ResolvesUniqueSlug
{
    /**
     * @param  class-string<Model>  $modelClass
     * @param  string|null  $provided  a slug typed by the admin; already
     *                                 checked for uniqueness by the form
     *                                 request, so it is taken as-is
     */
    protected function resolveUniqueSlug(
        string $modelClass,
        ?string $provided,
        string $title,
        ?Model $existing = null,
        string $fallback = 'item',
    ): string {
        if (filled($provided)) {
            return $provided;
        }

        // Editing without touching the title keeps the published URL.
        if ($existing && filled($existing->getAttribute('slug'))) {
            return $existing->getAttribute('slug');
        }

        $base = Str::slug($title) ?: $fallback;
        $slug = $base;
        $suffix = 2;

        while ($this->slugTaken($modelClass, $slug, $existing)) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * Soft-deleted rows still occupy the unique index, so they count.
     *
     * @param  class-string<Model>  $modelClass
     */
    private function slugTaken(string $modelClass, string $slug, ?Model $existing): bool
    {
        $query = $modelClass::query();

        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($modelClass), true)) {
            $query->withTrashed();
        }

        return $query
            ->where('slug', $slug)
            ->when($existing, fn ($q) => $q->whereKeyNot($existing->getKey()))
            ->exists();
    }
}
