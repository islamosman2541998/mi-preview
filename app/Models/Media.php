<?php

namespace App\Models;

use App\Enums\Locale;
use App\Enums\MediaSource;
use App\Models\Concerns\HasLocalizedFields;
use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Media extends Model
{
    use HasFactory, HasLocalizedFields;

    protected $table = 'media';

    protected $fillable = ['site_id', 'path', 'source', 'credit', 'meta', 'alt_ar', 'alt_en'];

    protected $attributes = [
        'source' => 'upload',
    ];

    protected function casts(): array
    {
        return [
            'source' => MediaSource::class,
            'meta' => 'array',
        ];
    }

    public function url(): ?string
    {
        return MediaUrl::for($this->path);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function alt(Locale|string|null $locale = null): ?string
    {
        return $this->localized('alt', $locale);
    }
}
