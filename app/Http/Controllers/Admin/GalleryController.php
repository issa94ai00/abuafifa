<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MediaCollection;
use App\Http\Controllers\Concerns\ResolvesUniqueSlug;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGalleryRequest;
use App\Http\Requests\Admin\UpdateGalleryRequest;
use App\Models\Gallery;
use App\Models\GalleryImage;
use App\Services\MediaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class GalleryController extends Controller
{
    use ResolvesUniqueSlug;

    private const PER_PAGE = [12, 24, 48, 96];

    public function __construct(
        private readonly MediaService $mediaService
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Gallery::class);

        $trash = $request->boolean('trash');

        // per_page came straight from the query string, so `?per_page=99999`
        // would hydrate every gallery and its cover in one request.
        $perPage = (int) $request->integer('per_page', 12);
        $perPage = in_array($perPage, self::PER_PAGE, true) ? $perPage : 12;

        $galleries = Gallery::query()
            ->withCount('images')
            ->with('cover')
            ->when($trash, fn (Builder $q) => $q->onlyTrashed())
            ->when($request->filled('search'), function (Builder $q) use ($request) {
                $search = '%'.trim($request->string('search')).'%';

                $q->where(fn (Builder $q) => $q
                    ->where('title_ar', 'like', $search)
                    ->orWhere('title_en', 'like', $search)
                    ->orWhere('slug', 'like', $search));
            })
            ->when($request->has('status') && $request->input('status') !== '',
                fn (Builder $q) => $q->where('is_active', $request->boolean('status')))
            ->orderBy('sort_order')
            ->latest()
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Gallery $gallery) => $this->presentRow($gallery));

        return Inertia::render('Admin/Galleries/Index', [
            'galleries' => $galleries,
            'stats'     => $this->stats(),
            'filters'   => [
                'search'   => $request->string('search')->toString() ?: '',
                'status'   => $request->has('status') ? $request->input('status') : '',
                'trash'    => $trash,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function show(Gallery $gallery)
    {
        Gate::authorize('view', $gallery);

        $gallery->load(['images.media', 'cover'])->loadCount('images');

        return Inertia::render('Admin/Galleries/Show', [
            // Presented rather than handed over raw: the model has no
            // $appends, so `cover_url` was undefined on this page and the
            // cover never rendered.
            'gallery' => $this->presentRow($gallery),
            'images'  => $gallery->images->map(fn (GalleryImage $img) => [
                'id'         => $img->id,
                'url'        => $img->media?->url,
                'thumb'      => $img->media?->thumb_url,
                'caption'    => $img->caption_ar,
                'caption_en' => $img->caption_en,
                'sort'       => $img->sort_order,
                'featured'   => (bool) $img->is_featured,
                'size'       => $img->media?->readable_size,
                'dimensions' => $img->media?->width && $img->media?->height
                    ? "{$img->media->width}×{$img->media->height}"
                    : null,
            ])->values(),
        ]);
    }

    public function store(StoreGalleryRequest $request)
    {
        Gate::authorize('create', Gallery::class);

        $data = $request->validated();
        $failures = [];

        $gallery = DB::transaction(function () use ($request, $data, &$failures) {
            $cover = $request->hasFile('cover_image')
                ? $this->mediaService->storeOne($request->file('cover_image'), MediaCollection::GalleryCover->value)
                : null;

            try {
                $gallery = Gallery::create($this->attributesFrom($data) + [
                    'slug' => $this->resolveUniqueSlug(
                        Gallery::class, $data['slug'] ?? null, $data['title_ar'], null, 'gallery'
                    ),
                    'cover_media_id' => $cover?->getKey(),
                ]);
            } catch (\Throwable $e) {
                // The rollback drops the Media row but not the file it
                // already wrote, so that is cleaned up by hand.
                $cover && $this->mediaService->delete($cover);

                throw $e;
            }

            if ($request->hasFile('images')) {
                $this->attachImages($gallery, $request->file('images'), $failures);
            }

            return $gallery;
        });

        return back()->with(...$this->uploadFlash(
            "تم إضافة المعرض «{$gallery->title_ar}» بنجاح.", $failures
        ));
    }

    public function update(UpdateGalleryRequest $request, Gallery $gallery)
    {
        Gate::authorize('update', $gallery);

        $data = $request->validated();
        $failures = [];

        DB::transaction(function () use ($request, $data, $gallery, &$failures) {
            $gallery->loadMissing('cover');
            $previousCover = $gallery->cover;

            $attributes = $this->attributesFrom($data) + [
                'slug' => $this->resolveUniqueSlug(
                    Gallery::class, $data['slug'] ?? null, $data['title_ar'], $gallery, 'gallery'
                ),
            ];

            if ($request->hasFile('cover_image')) {
                $attributes['cover_media_id'] = $this->mediaService
                    ->storeOne($request->file('cover_image'), MediaCollection::GalleryCover->value)
                    ->getKey();
            } elseif ($request->boolean('remove_cover')) {
                $attributes['cover_media_id'] = null;
            }

            $gallery->update($attributes);

            // Released only once the column no longer points at it —
            // otherwise deleteIfUnused() counts this gallery as a user.
            if (array_key_exists('cover_media_id', $attributes) && $previousCover) {
                $this->mediaService->deleteIfUnused($previousCover);
            }

            if ($request->hasFile('images')) {
                $this->attachImages($gallery, $request->file('images'), $failures);
            }
        });

        return back()->with(...$this->uploadFlash(
            "تم تحديث المعرض «{$gallery->title_ar}» بنجاح.", $failures
        ));
    }

    public function destroy(Gallery $gallery)
    {
        Gate::authorize('delete', $gallery);

        $gallery->delete();

        // Soft delete — the old message said "حذف" (deleted outright),
        // which read as unrecoverable when the row is still restorable.
        return back()->with('success', "تم نقل المعرض «{$gallery->title_ar}» إلى سلة المحذوفات.");
    }

    public function restore(Gallery $gallery)
    {
        Gate::authorize('restore', $gallery);

        $gallery->restore();

        return back()->with('success', "تم استعادة المعرض «{$gallery->title_ar}» بنجاح.");
    }

    // ------------------------------------------------------------ image rows

    public function removeImage(Gallery $gallery, GalleryImage $image)
    {
        Gate::authorize('update', $gallery);

        // Nested binding does not verify the relationship on its own, so
        // an image id from another gallery is rejected here.
        abort_unless($image->gallery_id === $gallery->getKey(), 404);

        DB::transaction(function () use ($gallery, $image) {
            $media = $image->media;

            $image->delete();

            // Dropping the join row used to leave the file and its Media
            // row behind forever — nothing else references a gallery image.
            $media && $this->mediaService->deleteIfUnused($media);

            $this->normalizeOrder($gallery);
        });

        return back()->with('success', 'تم حذف الصورة.');
    }

    public function updateImage(Request $request, Gallery $gallery, GalleryImage $image)
    {
        Gate::authorize('update', $gallery);

        abort_unless($image->gallery_id === $gallery->getKey(), 404);

        $validated = $request->validate([
            'caption_ar'  => ['nullable', 'string', 'max:255'],
            'caption_en'  => ['nullable', 'string', 'max:255'],
            'is_featured' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($validated, $gallery, $image) {
            if ($validated['is_featured'] ?? false) {
                // A gallery leads with one image, not several.
                GalleryImage::query()
                    ->where('gallery_id', $gallery->getKey())
                    ->whereKeyNot($image->getKey())
                    ->where('is_featured', true)
                    ->update(['is_featured' => false]);
            }

            $image->update($validated);
        });

        return back()->with('success', 'تم تحديث الصورة.');
    }

    /**
     * Move one image up or down in the gallery order.
     *
     * The previous implementation compared `sort_order` with < and >, so
     * it silently did nothing whenever neighbouring images shared a value
     * — which they did, because attachImages() left gaps and duplicates
     * and freshly seeded rows all defaulted to 0. Ordering is normalised
     * to 0..n-1 first, then the two positions are swapped, so a move is
     * always exactly one step and always takes effect.
     */
    public function moveImage(Request $request, Gallery $gallery, GalleryImage $image)
    {
        Gate::authorize('update', $gallery);

        abort_unless($image->gallery_id === $gallery->getKey(), 404);

        $direction = $request->string('direction')->toString() === 'up' ? 'up' : 'down';

        $moved = DB::transaction(function () use ($gallery, $image, $direction) {
            $ordered = $this->normalizeOrder($gallery);

            $index = $ordered->search(fn (GalleryImage $row) => $row->getKey() === $image->getKey());

            if ($index === false) {
                return false;
            }

            $target = $direction === 'up' ? $index - 1 : $index + 1;

            if ($target < 0 || $target >= $ordered->count()) {
                return false;
            }

            $a = $ordered[$index];
            $b = $ordered[$target];

            [$a->sort_order, $b->sort_order] = [$b->sort_order, $a->sort_order];

            $a->save();
            $b->save();

            return true;
        });

        return $moved
            ? back()->with('success', 'تم تحديث ترتيب الصور.')
            : back()->with('warning', 'لا يمكن نقل الصورة أبعد من ذلك.');
    }

    /**
     * Reorder the whole gallery in one request.
     *
     * Dragging a cover from position 12 to position 1 through the up/down
     * buttons was eleven round trips; the grid sends the final order once.
     */
    public function reorderImages(Request $request, Gallery $gallery)
    {
        Gate::authorize('update', $gallery);

        $validated = $request->validate([
            'ids'   => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['integer'],
        ]);

        DB::transaction(function () use ($gallery, $validated) {
            $byId = GalleryImage::query()
                ->where('gallery_id', $gallery->getKey())
                ->get()
                ->keyBy('id');

            $position = 0;

            // Only ids that actually belong to this gallery are honoured;
            // anything else in the payload is ignored rather than trusted.
            foreach ($validated['ids'] as $id) {
                $row = $byId->get($id);

                if ($row === null) {
                    continue;
                }

                if ($row->sort_order !== $position) {
                    $row->update(['sort_order' => $position]);
                }

                $byId->forget($id);
                $position++;
            }

            // Anything the client did not mention keeps its relative order
            // at the end rather than being dropped from the sequence.
            foreach ($byId->sortBy('sort_order') as $row) {
                if ($row->sort_order !== $position) {
                    $row->update(['sort_order' => $position]);
                }

                $position++;
            }
        });

        return back()->with('success', 'تم حفظ ترتيب الصور.');
    }

    // --------------------------------------------------------------- helpers

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributesFrom(array $data): array
    {
        return [
            'title_ar'       => $data['title_ar'],
            'title_en'       => $data['title_en'] ?? null,
            'description_ar' => $data['description_ar'] ?? null,
            'description_en' => $data['description_en'] ?? null,
            'sort_order'     => $data['sort_order'] ?? 0,
            'is_active'      => $data['is_active'] ?? true,
        ];
    }

    /**
     * @param  array<string, string>  $failures  filled by reference
     */
    private function attachImages(Gallery $gallery, iterable $files, array &$failures = []): void
    {
        // Computed once. The old version re-counted inside the loop after
        // each insert, so positions came out 0, 2, 4, … instead of 0, 1, 2.
        $max = GalleryImage::query()
            ->where('gallery_id', $gallery->getKey())
            ->max('sort_order');

        $next = $max === null ? 0 : (int) $max + 1;

        $stored = $this->mediaService->storeUploads(
            $files,
            MediaCollection::GalleryImage->value,
            null,
            $failures,
        );

        foreach ($stored as $media) {
            // The (gallery_id, media_id) pair is unique; re-uploading the
            // same file produces a fresh Media row, so this cannot clash.
            $gallery->images()->create([
                'media_id'   => $media->getKey(),
                'sort_order' => $next++,
                'caption_ar' => $media->alt_ar,
                'caption_en' => $media->alt_en,
            ]);
        }
    }

    /**
     * Rewrite sort_order to a dense 0..n-1 sequence.
     *
     * @return \Illuminate\Support\Collection<int, GalleryImage>
     */
    private function normalizeOrder(Gallery $gallery)
    {
        $ordered = GalleryImage::query()
            ->where('gallery_id', $gallery->getKey())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($ordered->values() as $position => $row) {
            if ($row->sort_order !== $position) {
                $row->sort_order = $position;
                $row->save();
            }
        }

        return $ordered->values();
    }

    /**
     * A partly successful batch upload reports both halves.
     *
     * @param  array<string, string>  $failures
     * @return array{0: string, 1: string}
     */
    private function uploadFlash(string $success, array $failures): array
    {
        if ($failures === []) {
            return ['success', $success];
        }

        $names = implode('، ', array_slice(array_keys($failures), 0, 5));
        $count = count($failures);

        return ['warning', "{$success} تعذّر رفع {$count} صورة: {$names}."];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentRow(Gallery $gallery): array
    {
        return [
            'id'              => $gallery->id,
            'title_ar'        => $gallery->title_ar,
            'title_en'        => $gallery->title_en,
            'slug'            => $gallery->slug,
            'description_ar'  => $gallery->description_ar,
            'description_en'  => $gallery->description_en,
            'sort_order'      => (int) $gallery->sort_order,
            'is_active'       => (bool) $gallery->is_active,
            'images_count'    => (int) ($gallery->images_count ?? 0),
            'cover_url'       => $gallery->cover?->url,
            'cover_thumb_url' => $gallery->cover?->thumb_url,
            'trashed'         => $gallery->trashed(),
            'deleted_at'      => $gallery->deleted_at?->toIso8601String(),
            'created_at'      => $gallery->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function stats(): array
    {
        $row = Gallery::query()
            ->withTrashed()
            ->selectRaw('count(case when deleted_at is null then 1 end) as total')
            ->selectRaw('count(case when deleted_at is null and is_active = 1 then 1 end) as active')
            ->selectRaw('count(case when deleted_at is null and is_active = 0 then 1 end) as hidden')
            ->selectRaw('count(case when deleted_at is not null then 1 end) as trashed')
            ->first();

        return [
            'total'   => (int) ($row->total ?? 0),
            'active'  => (int) ($row->active ?? 0),
            'hidden'  => (int) ($row->hidden ?? 0),
            'trashed' => (int) ($row->trashed ?? 0),
        ];
    }
}
