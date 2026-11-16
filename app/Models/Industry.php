<?php

namespace App\Models;

use App\Enums\Locale;
use App\Models\Concerns\HasLocalizedFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * sections_blueprint shape:
 * {
 *   "pages": [
 *     { "slug": "home", "title": {"ar": "الرئيسية", "en": "Home"}, "is_home": true,
 *       "sections": ["hero", {"type": "services", "variant": "list"}, "cta"] },
 *     …
 *   ]
 * }
 */
class Industry extends Model
{
    use HasFactory, HasLocalizedFields;

    protected $fillable = [
        'key', 'name_ar', 'name_en', 'sections_blueprint', 'stock_keywords', 'ai_hints', 'default_palette',
    ];

    protected function casts(): array
    {
        return [
            'sections_blueprint' => 'array',
            'stock_keywords' => 'array',
            'default_palette' => 'array',
        ];
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    public function name(Locale|string|null $locale = null): ?string
    {
        return $this->localized('name', $locale);
    }
}
