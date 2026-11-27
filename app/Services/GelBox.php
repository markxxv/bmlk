<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class GelBox
{
    public const CATEGORY_ID = 1;

    public const DEFAULT_SIZE = 6;

    public const MAX_SIZE = 99;

    public static function eligible(): Builder
    {
        return Product::query()
            ->where('category_id', self::CATEGORY_ID)
            ->where('active', true)
            ->where('available', true)
            ->where('requires_gel_selection', false);
    }

    public static function snapshot(Product $gel, string $locale): array
    {
        $french = $gel->getTranslation('name', 'fr', false);

        return [
            'id' => $gel->id,
            'name' => $gel->getTranslation('name', $locale, false) ?: $french,
            'name_en' => $gel->getTranslation('name', 'en', false) ?: $french,
            'sku' => $gel->sku,
            'image' => $gel->storefrontImageUrl('thumb'),
        ];
    }

    public static function normalize(Product $box, mixed $selection, string $locale): array
    {
        if (! $box->requires_gel_selection && ($selection === [] || $selection === null)) {
            return [];
        }

        $required = (int) $box->gel_selection_count;

        if (! $box->requires_gel_selection || $required < 1 || $required > self::MAX_SIZE
            || ! is_array($selection) || ! array_is_list($selection) || count($selection) !== $required) {
            self::invalid($box);
        }

        $ids = [];
        foreach ($selection as $entry) {
            $id = is_array($entry) ? ($entry['id'] ?? null) : null;
            if ((! is_int($id) && ! is_string($id)) || ! filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
                self::invalid($box);
            }
            $ids[] = (int) $id;
        }

        $gels = self::eligible()->whereKeyNot($box->id)->whereIn('id', $ids)
            ->with('media')->get()->keyBy('id');

        sort($ids, SORT_NUMERIC);

        return array_map(function (int $id) use ($gels, $locale, $box): array {
            $gel = $gels->get($id);
            if (! $gel) {
                self::invalid($box);
            }

            return self::snapshot($gel, $locale);
        }, $ids);
    }

    private static function invalid(Product $box): never
    {
        $count = (int) $box->gel_selection_count;
        $message = $box->requires_gel_selection && $count >= 1 && $count <= self::MAX_SIZE
            ? trans_choice('Choisissez un gel disponible pour votre coffret.|Choisissez :count gels disponibles pour votre coffret.', $count)
            : __('La composition de votre coffret est invalide.');

        throw ValidationException::withMessages([
            'cart_items' => $message,
            'box_gels' => $message,
        ]);
    }
}
