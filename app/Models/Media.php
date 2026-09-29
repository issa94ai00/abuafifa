<?php

namespace App\Models;

use App\Enums\ActivityAction;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class Media extends Model
{
    use HasFactory, LogsActivity;

    /**
     * @return array<string, ActivityAction>
     */
    protected static function activityActions(): array
    {
        return [
            'created' => ActivityAction::MediaUploaded,
            'updated' => ActivityAction::MediaUpdated,
            'deleted' => ActivityAction::MediaDeleted,
        ];
    }

    /**
     * Only the descriptive fields are mass assignable.
     *
     * Storage facts (disk, directory, filename, mime type, size, owner)
     * are decided by MediaService and written with forceFill, so no
     * request body can ever point a Media row at another file.
     *
     * @var list<string>
     */
    protected $fillable = [
        'alt_ar',
        'alt_en',
        'caption',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'is_processed' => 'boolean',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getFullPathAttribute(): string
    {
        return trim($this->directory . '/' . $this->filename, '/');
    }

    /**
     * Absolute URL for the stored original file.
     *
     * Files in a non-public collection live on the private disk and are
     * never linked directly; they are served through an authorised,
     * signed controller route instead.
     */
    public function getUrlAttribute(): string
    {
        if ($this->disk !== 'public') {
            return $this->securedUrl();
        }

        return Storage::disk($this->disk)->url($this->full_path);
    }

    /**
     * Absolute URL for the generated thumb variant (if present).
     */
    public function getThumbUrlAttribute(): string
    {
        $thumb = $this->thumbPath();

        if ($this->disk !== 'public') {
            return $this->securedUrl('thumb');
        }

        return Storage::disk($this->disk)->exists($thumb)
            ? Storage::disk($this->disk)->url($thumb)
            : $this->url;
    }

    /**
     * Thumbs are written as `<uuid>-thumb.<ext>` inside a `thumbs/`
     * subdirectory by MediaService.
     */
    public function thumbPath(): string
    {
        $name = pathinfo((string) $this->filename, PATHINFO_FILENAME);
        $extension = pathinfo((string) $this->filename, PATHINFO_EXTENSION);

        return trim($this->directory, '/')."/thumbs/{$name}-thumb.{$extension}";
    }

    /**
     * Short-lived signed URL for a private file.
     */
    public function securedUrl(string $variant = 'original'): string
    {
        return URL::temporarySignedRoute(
            'admin.media.file',
            now()->addMinutes(15),
            ['media' => $this->getKey(), 'variant' => $variant],
        );
    }

    public function getReadableSizeAttribute(): string
    {
        if ($this->size >= 1048576) {
            return number_format($this->size / 1048576, 1).' MB';
        }

        return number_format($this->size / 1024, 0).' KB';
    }
}