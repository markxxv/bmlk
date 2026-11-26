<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class GelBox
{
    public const CATEGORY_ID = 1;

    public const SIZE = 6;

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

        if (! $box->requires_gel_selection || ! is_array($selection)
            || ! array_is_list($selection) || count($selection) !== self::SIZE) {
            self::invalid();
        }

        $ids = [];
        foreach ($selection as $entry) {
            $id = is_array($entry) ? ($entry['id'] ?? null) : null;
            if ((! is_int($id) && ! is_string($id)) || ! filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
                self::invalid();
            }
            $ids[] = (int) $id;
        }

        $gels = self::eligible()->whereKeyNot($box->id)->whereIn('id', $ids)
            ->with('media')->get()->keyBy('id');

        sort($ids, SORT_NUMERIC);

        return array_map(function (int $id) use ($gels, $locale): array {
            $gel = $gels->get($id);
            if (! $gel) {
                self::invalid();
            }

            return self::snapshot($gel, $locale);
        }, $ids);
    }

    private static function invalid(): never
    {
        $message = __('Choisissez six gels disponibles pour votre coffret.');

        throw ValidationException::withMessages([
            'cart_items' => $message,
            'box_gels' => $message,
        ]);
    }
}
