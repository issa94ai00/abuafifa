<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_type',
        'owner_id',
        'route_name',
        'meta_title',
        'meta_description',
        'keywords',
        'og_title',
        'og_description',
        'og_image_media_id',
        'canonical_url',
        'robots',
    ];

    protected function casts(): array
    {
        return [
            'robots' => 'string',
        ];
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function ogImage(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Media::class, 'og_image_media_id');
    }
}