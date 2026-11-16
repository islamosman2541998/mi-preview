<?php

namespace App\Models;

use App\Enums\Locale;
use App\Enums\NavItemType;
use App\Models\Concerns\HasLocalizedFields;
use App\Support\PreviewUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NavItem extends Model
{
    use HasFactory, HasLocalizedFields;

    protected $fillable = ['site_id', 'parent_id', 'label_ar', 'label_en', 'type', 'target', 'sort', 'is_cta'];

    protected $attributes = [
        'type' => 'page',
        'sort' => 0,
        'is_cta' => false,
    ];

    protected function casts(): array
    {
        return [
            'type' => NavItemType::class,
            'is_cta' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort')->orderBy('id');
    }

    public function label(Locale|string|null $locale = null): ?string
    {
        return $this->localized('label', $locale);
    }

    /**
     * Resolved href. Page/anchor links are relative to the preview host.
     * The home page is linked by its slug or by an empty target.
     */
    public function href(Site $site, ?Locale $locale = null, ?string $homeSlug = null): ?string
    {
        $target = trim((string) $this->target);

        return match ($this->type) {
            NavItemType::Page => PreviewUrl::path($locale, ($target === '' || $target === $homeSlug) ? null : $target),
            NavItemType::Anchor => PreviewUrl::path($locale).'#'.ltrim($target, '#'),
            NavItemType::External => $target ?: null,
            NavItemType::Whatsapp => $target !== ''
                ? 'https://wa.me/'.preg_replace('/\D+/', '', $target)
                : $site->whatsappLink(),
            NavItemType::Phone => $target !== ''
                ? 'tel:'.preg_replace('/[^\d+]+/', '', $target)
                : $site->phoneLink(),
        };
    }

    /** Value for data-track on the rendered link (tracking JS). */
    public function trackAs(): ?string
    {
        return match (true) {
            $this->type === NavItemType::Whatsapp => 'whatsapp',
            $this->type === NavItemType::Phone => 'phone',
            $this->is_cta => 'cta',
            default => null,
        };
    }
}
