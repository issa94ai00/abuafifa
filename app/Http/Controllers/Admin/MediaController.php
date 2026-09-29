<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MediaCollection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMediaRequest;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    private const PER_PAGE = [24, 48, 96];

    public function __construct(
        private readonly MediaService $mediaService
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Media::class);

        // Unvalidated before: `?per_page=100000` hydrated the whole media
        // library, uploader relation included, into one response.
        $perPage = (int) $request->integer('per_page', 30);
        $perPage = in_array($perPage, self::PER_PAGE, true) ? $perPage : 24;

        // Only a known collection narrows the query; a junk value used to
        // silently return an empty library rather than everything.
        $collection = MediaCollection::tryFrom($request->string('collection')->toString());

        $media = Media::query()
            ->with('uploader:id,name')
            ->when($collection, fn ($q) => $q->where('collection', $collection->value))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = '%'.trim($request->string('search')).'%';

                $q->where(fn ($q) => $q
                    ->where('original_name', 'like', $search)
                    ->orWhere('alt_ar', 'like', $search)
                    ->orWhere('alt_en', 'like', $search)
                    ->orWhere('caption', 'like', $search));
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Media $item) => $this->presentRow($item));

        return Inertia::render('Admin/Media/Index', [
            'media' => $media,
            'stats' => $this->stats(),
            'filters' => [
                'search'     => $request->string('search')->toString() ?: '',
                'collection' => $collection?->value ?? '',
                'per_page'   => $perPage,
            ],
            'collections' => MediaCollection::options(),
        ]);
    }

    public function store(StoreMediaRequest $request)
    {
        Gate::authorize('create', Media::class);

        $failures = [];

        $stored = $this->mediaService->storeUploads(
            $request->file('files'),
            $request->validated('collection'),
            null,
            $failures,
        );

        // A file that failed to decode used to be dropped silently while
        // the flash still counted it as uploaded.
        if ($failures !== []) {
            $names = implode('، ', array_slice(array_keys($failures), 0, 5));

            return back()->with('warning', $stored->isEmpty()
                ? "تعذّر رفع الصور: {$names}."
                : "تم رفع {$stored->count()} صورة. تعذّر رفع ".count($failures)." صورة: {$names}.");
        }

        return back()->with('success', "تم رفع {$stored->count()} صورة بنجاح.");
    }

    public function update(Request $request, Media $media)
    {
        Gate::authorize('update', $media);

        // Only the descriptive fields — the Media model is not mass
        // assignable beyond these, so nothing here can repoint the file.
        $validated = $request->validate([
            'alt_ar' => ['nullable', 'string', 'max:255'],
            'alt_en' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:255'],
        ]);

        $media->update($validated);

        return back()->with('success', 'تم تحديث بيانات الصورة.');
    }

    public function destroy(Media $media)
    {
        Gate::authorize('delete', $media);

        // Deleting a file a product or offer still points at left a broken
        // image in the storefront with nothing to explain it. The library
        // now refuses and says where the file is used.
        $uses = $this->mediaService->usageCount($media);

        if ($uses > 0) {
            return back()->with('warning',
                "لا يمكن حذف هذه الصورة لأنها مستخدمة في {$uses} عنصر. أزل الصورة من تلك العناصر أولاً.");
        }

        $this->mediaService->delete($media);

        return back()->with('success', 'تم حذف الصورة.');
    }

    /**
     * Serve a file that lives on the private disk.
     *
     * Files in non-public collections are deliberately outside the
     * document root, so the only way to read one is through this action:
     * a signed URL *and* an authorisation check, not one or the other.
     * The response is forced to an inert content type so a browser will
     * never execute whatever it receives.
     */
    public function file(Request $request, Media $media): StreamedResponse
    {
        Gate::authorize('view', $media);

        $variant = $request->string('variant')->toString() === 'thumb' ? 'thumb' : 'original';
        $path = $variant === 'thumb' ? $media->thumbPath() : $media->full_path;

        $disk = Storage::disk($media->disk);

        abort_unless($disk->exists($path), 404);

        return $disk->response($path, $media->original_name, [
            'Content-Type' => $media->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'inline; filename="'.addslashes((string) $media->original_name).'"',
            'Cache-Control' => 'private, max-age=900',
        ]);
    }

    // --------------------------------------------------------------- reading

    /**
     * The library reads `readable_size`, which is an accessor and not in
     * the model's $appends — handing the raw model over meant the size
     * column rendered empty on every tile. Presenting the row explicitly
     * also keeps `disk`, `directory` and `user_id` out of the payload.
     *
     * @return array<string, mixed>
     */
    private function presentRow(Media $media): array
    {
        return [
            'id'            => $media->id,
            'collection'    => $media->collection,
            'original_name' => $media->original_name,
            'alt_ar'        => $media->alt_ar,
            'alt_en'        => $media->alt_en,
            'caption'       => $media->caption,
            'mime_type'     => $media->mime_type,
            'extension'     => $media->extension,
            'size'          => (int) $media->size,
            'readable_size' => $media->readable_size,
            'width'         => $media->width,
            'height'        => $media->height,
            'is_processed'  => (bool) $media->is_processed,
            'url'           => $media->url,
            'thumb_url'     => $media->thumb_url,
            'uploader'      => $media->uploader?->name,
            'created_at'    => $media->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function stats(): array
    {
        // One pass instead of four separate table scans.
        $row = Media::query()
            ->selectRaw('count(*) as total')
            ->selectRaw('coalesce(sum(size), 0) as storage')
            ->selectRaw('count(case when created_at >= ? then 1 end) as month_uploads', [now()->startOfMonth()])
            ->selectRaw('count(case when is_processed = 1 then 1 end) as processed')
            ->first();

        return [
            'total'         => (int) ($row->total ?? 0),
            'storage'       => (int) ($row->storage ?? 0),
            'month_uploads' => (int) ($row->month_uploads ?? 0),
            'processed'     => (int) ($row->processed ?? 0),
        ];
    }
}
