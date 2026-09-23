<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TranslatableSlugObserver
{
    public function creating(Model $model): void
    {
        $this->syncMissingSlugs($model);
    }

    private function syncMissingSlugs(Model $model): void
    {
        $sourceAttribute = $model->getTranslatableSlugSourceAttribute();
        $targetAttribute = $model->getTranslatableSlugTargetAttribute();

        $titles = $model->getTranslations($sourceAttribute);
        $slugs = $model->getTranslations($targetAttribute);

        $locales = $this->resolveLocales($titles, $slugs);

        $reservedSlugs = collect($slugs)
            ->map(fn (mixed $slug): string => $this->normalize($slug))
            ->filter()
            ->values()
            ->all();

        foreach ($locales as $locale) {
            $title = $this->normalize($titles[$locale] ?? null);
            $slug = $this->normalize($slugs[$locale] ?? null);

            if ($title === '' || $slug !== '') {
                continue;
            }

            $generatedSlug = $this->generateUniqueSlug(
                model: $model,
                title: $title,
                targetAttribute: $targetAttribute,
                locales: $locales,
                reservedSlugs: $reservedSlugs,
            );

            if ($generatedSlug === null) {
                continue;
            }

            $model->setTranslation($targetAttribute, $locale, $generatedSlug);
            $reservedSlugs[] = $generatedSlug;
        }
    }

    private function resolveLocales(array $titles, array $slugs): array
    {
        $configuredLocales = config('app.supported_locales', ['fr', 'en', 'ro']);

        if (! is_array($configuredLocales) || $configuredLocales === []) {
            $configuredLocales = ['fr', 'en', 'ro'];
        }

        if (! array_is_list($configuredLocales)) {
            $configuredLocales = array_keys($configuredLocales);
        }

        return collect($configuredLocales)
            ->merge(array_keys($titles))
            ->merge(array_keys($slugs))
            ->filter(
                fn (mixed $locale): bool => is_string($locale)
                    && preg_match('/^[a-zA-Z0-9_-]+$/', $locale) === 1
            )
            ->unique()
            ->values()
            ->all();
    }

    private function generateUniqueSlug(
        Model $model,
        string $title,
        string $targetAttribute,
        array $locales,
        array $reservedSlugs,
    ): ?string {
        $baseSlug = Str::slug($title);

        if ($baseSlug === '') {
            return null;
        }

        $slug = $baseSlug;
        $counter = 1;

        while (
            in_array($slug, $reservedSlugs, true)
            || $this->slugExists(
                model: $model,
                slug: $slug,
                targetAttribute: $targetAttribute,
                locales: $locales,
            )
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function slugExists(
        Model $model,
        string $slug,
        string $targetAttribute,
        array $locales,
    ): bool {
        if ($locales === []) {
            return false;
        }

        return $model
            ->newQueryWithoutScopes()
            ->where(function (Builder $query) use ($targetAttribute, $locales, $slug): void {
                foreach ($locales as $locale) {
                    $query->orWhere("{$targetAttribute}->{$locale}", $slug);
                }
            })
            ->exists();
    }

    private function normalize(mixed $value): string
    {
        return trim((string) $value);
    }
}
