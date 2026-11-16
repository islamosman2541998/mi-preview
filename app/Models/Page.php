<?php

namespace App\Models;

use App\Enums\Locale;
use App\Models\Concerns\HasLocalizedFields;
use App\Support\PreviewUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    use HasFactory, HasLocalizedFields;

    protected $fillable = ['site_id', 'slug', 'title_ar', 'title_en', 'is_home', 'sort', 'is_published'];

    protected $attributes = [
        'is_home' => false,
        'sort' => 0,
        'is_published' => true,
    ];

    protected function casts(): array
    {
        return [
            'is_home' => 'boolean',
            'is_published' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('sort')->orderBy('id');
    }

    public function visibleSections(): HasMany
    {
        return $this->sections()->where('is_visible', true);
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    public function title(Locale|string|null $locale = null): ?string
    {
        return $this->localized('title', $locale);
    }

    /** Relative path inside the preview site ("/" for the home page). */
    public function path(?Locale $locale = null): string
    {
        return PreviewUrl::path($locale, $this->is_home ? null : $this->slug);
    }
}
