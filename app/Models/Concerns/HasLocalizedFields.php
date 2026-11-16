<?php

namespace App\Models\Concerns;

use App\Enums\Locale;

/**
 * Reads "{field}_{locale}" columns (title_ar / title_en …) and falls back to
 * the default locale (Arabic) when the requested translation is empty.
 */
trait HasLocalizedFields
{
    public function localized(string $field, Locale|string|null $locale = null): ?string
    {
        $locale = $locale instanceof Locale ? $locale->value : ($locale ?: app()->getLocale());
        $fallback = config('preview.default_locale', 'ar');

        $value = $this->getAttribute($field.'_'.$locale);

        if (blank($value) && $locale !== $fallback) {
            $value = $this->getAttribute($field.'_'.$fallback);
        }

        return $value;
    }
}
