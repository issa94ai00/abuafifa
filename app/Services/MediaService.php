<?php

namespace App\Services;

use App\Enums\MediaCollection;
use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\AvifEncoder;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Interfaces\EncoderInterface;
use Intervention\Image\Laravel\Facades\Image;

/**
 * Stores uploaded images.
 *
 * Security rules this service enforces, regardless of what the caller
 * validated:
 *
 *  - The stored filename is a UUID plus an extension derived from the
 *    file's *detected* MIME type. The client's original name and
 *    extension are kept only as a display label, never as a path.
 *  - Every image is re-encoded before it is written. Re-encoding drops
 *    EXIF payloads and destroys anything smuggled in the file that is
 *    not pixel data, so a polyglot image/PHP file cannot survive.
 *  - Anything that fails to decode is rejected outright rather than
 *    stored as-is.
 */
class MediaService
{
    /**
     * Every table => column that can point at a media row.
     *
     * Media is referenced by plain nullable foreign keys rather than a
     * polymorphic pivot, so "is this file still in use?" has to be asked
     * one column at a time. Anything added here is picked up by both
     * deleteIfUnused() and the admin delete guard.
     */
    private const REFERENCES = [
        'products'         => 'cover_media_id',
        'product_images'   => 'media_id',
        'product_variants' => 'media_id',
        'categories'       => 'image_media_id',
        'offers'           => 'image_media_id',
        'galleries'        => 'cover_media_id',
        'gallery_images'   => 'media_id',
        'seo_settings'     => 'og_image_media_id',
    ];

    /**
     * Detected MIME type => the extension we will store it under.
     */
    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/avif' => 'avif',
    ];


    /**
     * Store an array of UploadedFile files and create Media rows.
     *
     * A batch is not all-or-nothing: one unreadable file among twenty
     * should not discard the nineteen that decoded. Failures are
     * collected in $failures (keyed by display name) so the caller can
     * tell the admin which files did not make it — previously they were
     * dropped silently and the flash message still said "uploaded 20".
     *
     * @param  iterable<UploadedFile>  $files
     * @param  array<string, string>  $failures  filled by reference
     * @return EloquentCollection<int, Media>
     */
    public function storeUploads(
        iterable $files,
        string $collection = 'default',
        ?int $userId = null,
        array &$failures = [],
    ): EloquentCollection {
        $user = $userId ? User::find($userId) : Auth::user();
        $user = $user instanceof User ? $user : null;

        $stored = [];

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            try {
                $stored[] = $this->storeOne($file, $collection, $user);
            } catch (\Throwable $e) {
                $name = $this->sanitizeDisplayName($file->getClientOriginalName());
                $failures[$name] = $e->getMessage();

                Log::warning("MediaService: upload failed for [{$name}]: {$e->getMessage()}");
            }
        }

        return new EloquentCollection($stored);
    }

    /**
     * @throws \RuntimeException when the upload is not a decodable image
     */
    public function storeOne(UploadedFile $file, string $collection = 'default', ?User $user = null): Media
    {
        $sourcePath = $file->getRealPath();

        if (! $sourcePath || ! $file->isValid()) {
            throw new \RuntimeException('Upload is not a valid file.');
        }

        $user ??= Auth::user() instanceof User ? Auth::user() : null;
        $displayName = $this->sanitizeDisplayName($file->getClientOriginalName());

        return $this->write($sourcePath, $collection, [
            'user_id' => $user?->getKey(),
            'original_name' => $displayName,
            'alt_ar' => $this->altFromFilename($file->getClientOriginalName()),
            'alt_en' => $this->altFromFilename($file->getClientOriginalName()),
        ]);
    }

    /**
     * Store a raw image from a path on disk (seeders, imports).
     *
     * Unlike storeOne this returns null rather than throwing: an import
     * run should log a bad file and carry on, not abort the batch.
     */
    public function storeFromPath(string $absolutePath, string $collection = 'default', array $meta = []): ?Media
    {
        if (! is_file($absolutePath) || ! is_readable($absolutePath)) {
            Log::warning("MediaService: file not found — {$absolutePath}");

            return null;
        }

        try {
            return $this->write($absolutePath, $collection, [
                'original_name' => $this->sanitizeDisplayName(basename($absolutePath)),
                'alt_ar' => $meta['alt_ar'] ?? $this->altFromFilename($absolutePath),
                'alt_en' => $meta['alt_en'] ?? $this->altFromFilename($absolutePath),
            ]);
        } catch (\Throwable $e) {
            Log::warning("MediaService: could not store [{$absolutePath}]: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Delete a Media row unless something still points at it.
     *
     * Media rows are referenced by several nullable foreign keys rather
     * than a polymorphic pivot, so "is anyone using this?" has to be
     * asked column by column.
     *
     * Order matters when swapping an image: repoint the owning column
     * first, *then* call this. Called while the owner still references
     * the old row it will always count one user and decline to delete,
     * which is exactly the orphan it is meant to prevent.
     */
    public function deleteIfUnused(Media $media): bool
    {
        if ($this->usageCount($media) > 0) {
            return false;
        }

        return $this->delete($media);
    }

    /**
     * How many records still reference this file.
     */
    public function usageCount(Media $media): int
    {
        $id = $media->getKey();
        $total = 0;

        foreach (self::REFERENCES as $table => $column) {
            $total += (int) DB::table($table)->where($column, $id)->count();
        }

        return $total;
    }

    public function makeThumbPath(Media $media): string
    {
        $name = pathinfo($media->filename, PATHINFO_FILENAME);
        $extension = pathinfo($media->filename, PATHINFO_EXTENSION);

        return $media->directory."/thumbs/{$name}-thumb.{$extension}";
    }

    public function delete(Media $media): bool
    {
        foreach ([$media->full_path, $this->makeThumbPath($media)] as $path) {
            Storage::disk($media->disk)->delete($path);
        }

        return (bool) $media->delete();
    }

    public function url(Media $media, string $variant = 'original'): string
    {
        $path = $variant === 'thumb' ? $this->makeThumbPath($media) : $media->full_path;

        return Storage::disk($media->disk)->url($path);
    }

    /**
     * Whether a collection's files are safe to expose without auth.
     */
    public static function isPublicCollection(string $collection): bool
    {
        return MediaCollection::tryFrom($collection)?->isPublic() ?? false;
    }

    // ---------------------------------------------------------------- helpers

    /**
     * Write a Media row from trusted, service-computed attributes.
     */
    private function persist(array $attributes): Media
    {
        $media = new Media;
        $media->forceFill($attributes)->save();

        return $media;
    }

    private function diskFor(string $collection): string
    {
        return self::isPublicCollection($collection) ? 'public' : 'private';
    }

    /**
     * Collections come from request input, so they are constrained to a
     * known list — otherwise `../../` in the value would escape the
     * media directory.
     */
    private function sanitizeCollection(string $collection): string
    {
        $collection = Str::of($collection)->lower()->replaceMatches('/[^a-z0-9\-_]/', '')->value();

        return $collection !== '' ? $collection : 'default';
    }

    private function detectMime(string $path): string
    {
        return @mime_content_type($path) ?: 'application/octet-stream';
    }

    /**
     * Decode once, write the original and the thumb, then persist.
     *
     * The previous flow called Image::decodePath() twice — once to
     * re-encode the original and again to make the thumb — which decoded
     * every upload into memory twice. A 6000x6000 source (the validation
     * ceiling) is ~144MB of raster per decode, so a batch upload could
     * exhaust memory_limit on the second pass. The thumb is now derived
     * from the already-downscaled instance.
     *
     * @param  array<string, mixed>  $meta
     *
     * @throws \RuntimeException when the file is not a storable image
     */
    private function write(string $sourcePath, string $collection, array $meta): Media
    {
        $collection = $this->sanitizeCollection($collection);

        $mime = $this->detectMime($sourcePath);
        $extension = self::MIME_EXTENSIONS[$mime] ?? null;

        if ($extension === null) {
            // Belt and braces: the SafeImage rule should already have
            // rejected this, but the service refuses to trust the caller.
            throw new \RuntimeException("Refusing to store unsupported media type [{$mime}].");
        }

        $disk = $this->diskFor($collection);
        $uuid = Str::uuid()->toString();
        $directory = "media/{$collection}/".now()->format('Y/m/d');
        $filename = "{$uuid}.{$extension}";
        $storePath = "{$directory}/{$filename}";
        $thumbPath = "{$directory}/thumbs/{$uuid}-thumb.{$extension}";

        // Re-encode rather than copying the original bytes. This is the
        // step that neutralises EXIF payloads and image/script polyglots.
        $image = Image::decodePath($sourcePath);

        if ($image->width() > 1920 || $image->height() > 1080) {
            $image = $image->scaleDown(1920, 1080);
        }

        $contents = (string) $image->encode($this->encoderFor($extension, full: true));
        $width = $image->width();
        $height = $image->height();

        $written = [];

        try {
            Storage::disk($disk)->put($storePath, $contents, ['visibility' => 'public']);
            $written[] = $storePath;

            // cover() mutates in place, so this has to run after the
            // original has already been encoded above.
            $thumb = (string) $image->cover(600, 600)->encode($this->encoderFor($extension, full: false));

            Storage::disk($disk)->put($thumbPath, $thumb, ['visibility' => 'public']);
            $written[] = $thumbPath;
            $thumbStored = true;
        } catch (\Throwable $e) {
            // A missing thumb is survivable — the model falls back to the
            // original — but a missing original is not.
            if ($written === []) {
                throw new \RuntimeException("Could not write image: {$e->getMessage()}", 0, $e);
            }

            Log::warning("MediaService: thumb generation failed: {$e->getMessage()}");
            $thumbStored = false;
        }

        try {
            return $this->persist($meta + [
                'disk' => $disk,
                'collection' => $collection,
                'directory' => $directory,
                'filename' => $filename,
                'mime_type' => $mime,
                'extension' => $extension,
                'size' => strlen($contents),
                'width' => $width,
                'height' => $height,
                'is_processed' => $thumbStored,
            ]);
        } catch (\Throwable $e) {
            // Without this the row is gone but the files remain, and
            // nothing in the admin can ever see or delete them again.
            foreach ($written as $path) {
                Storage::disk($disk)->delete($path);
            }

            throw $e;
        }
    }

    /**
     * `strip: true` drops every metadata block (EXIF, XMP, ICC comments)
     * on the way out — that is where GPS coordinates and injected
     * payloads hide. PNG has no strip flag in Intervention v4; re-encoding
     * from the decoded raster drops its ancillary chunks regardless.
     */
    private function encoderFor(string $extension, bool $full): EncoderInterface
    {
        return match ($extension) {
            'webp' => new WebpEncoder(quality: $full ? 82 : 78, strip: true),
            'png' => new PngEncoder,
            'avif' => new AvifEncoder(quality: $full ? 82 : 78, strip: true),
            default => new JpegEncoder(quality: $full ? 88 : 85, progressive: true, strip: true),
        };
    }

    /**
     * Strip anything path- or header-dangerous out of the display name.
     */
    private function sanitizeDisplayName(string $name): string
    {
        $name = basename($name);
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? '';
        $name = str_replace(['..', '/', '\\', '<', '>', '"', "'"], '', $name);

        return Str::limit(trim($name), 180, '') ?: 'image';
    }

    private function altFromFilename(string $filename): string
    {
        return Str::limit(
            $this->sanitizeDisplayName(pathinfo($filename, PATHINFO_FILENAME)),
            200,
            ''
        );
    }
}
