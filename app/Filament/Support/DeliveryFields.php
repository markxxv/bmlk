<?php

namespace App\Filament\Support;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;

final class DeliveryFields
{
    public static function schema(bool $product = false): array
    {
        return [
            Grid::make([
                'default' => 1,
                'sm' => 2,
            ])->schema([
                TextInput::make('delivery_weight')
                    ->label('Weight')
                    ->prefixIcon('lucide-scale')
                    ->suffix('g')
                    ->placeholder($product ? 'Category default' : null)
                    ->numeric()
                    ->step(1)
                    ->minValue(0),

                self::dimension('delivery_height', 'Height', 'lucide-arrow-up-down', $product),
                self::dimension('delivery_depth', 'Depth', 'lucide-layers', $product),
                self::dimension('delivery_length', 'Length', 'lucide-ruler', $product),
            ]),
        ];
    }

    private static function dimension(string $name, string $label, string $icon, bool $product): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->prefixIcon($icon)
            ->suffix('mm')
            ->placeholder($product ? 'Category default' : null)
            ->numeric()
            ->inputMode('decimal')
            ->step(0.1)
            ->minValue(0)
            // The database stores centimeters; the form displays millimeters.
            ->formatStateUsing(fn (mixed $state): ?float => filled($state) ? round((float) $state * 10, 1) : null)
            ->dehydrateStateUsing(fn (mixed $state): ?float => filled($state) ? round((float) $state / 10, 2) : null);
    }
}
