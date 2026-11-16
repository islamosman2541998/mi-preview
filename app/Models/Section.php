<?php

namespace App\Models;

use App\Enums\Locale;
use App\Enums\SectionType;
use App\Services\MediaLibrary;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * content = { "ar": {...}, "en": {...}, "shared": {...} }
 * "shared" holds locale-independent values (image paths, numbers, links)
 * so they are not duplicated per language.
 */
class Section extends Model
{
    use HasFactory;

    protected $fillable = ['page_id', 'type', 'variant', 'content', 'sort', 'is_visible'];

    protected $attributes = [
        'sort' => 0,
        'is_visible' => true,
    ];

    protected function casts(): array
    {
        return [
            'type' => SectionType::class,
            'content' => 'array',
            'is_visible' => 'boolean',
            'sort' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // images uploaded into a section field also land in the site's media library
        static::saved(function (Section $section) {
            if ($section->wasChanged('content') || $section->wasRecentlyCreated) {
                app(MediaLibrary::class)->registerSection($section);
            }
        });
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * Text content for a locale, falling back to Arabic, merged over the
     * shared values (locale keys win).
     */
    public function contentFor(Locale|string|null $locale = null): array
    {
        $locale = $locale instanceof Locale ? $locale->value : ($locale ?: app()->getLocale());
        $content = $this->content ?? [];
        $fallback = config('preview.default_locale', 'ar');

        $text = $content[$locale] ?? [];
        if ($text === [] && $locale !== $fallback) {
            $text = $content[$fallback] ?? [];
        }

        return array_replace($content['shared'] ?? [], $text);
    }

    /** DOM id used by anchors ("#services") and by tracking ([data-section]). */
    public function anchor(): string
    {
        return $this->type->value;
    }
}
