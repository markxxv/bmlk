<?php

namespace App\Filament\Support;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

final class DeliveryFields
{
    public static function schema(bool $product = false): array
    {
        return [
            Section::make('Parcel measurements')
                ->icon('lucide-package')
                ->description($product
                    ? 'Optional overrides. Leave fields empty to use the category defaults.'
                    : 'Default parcel measurements for products in this category.')
                ->schema([
                    Grid::make([
                        'default' => 1,
                        'sm' => 2,
                        'xl' => 4,
                    ])->schema([
                        TextInput::make('delivery_weight')
                            ->label('Weight')
                            ->prefixIcon('lucide-scale')
                            ->suffix('g')
                            ->numeric()
                            ->step(1)
                            ->minValue(0),

                        self::dimension('delivery_height', 'Height', 'lucide-arrow-up-down'),
                        self::dimension('delivery_depth', 'Depth', 'lucide-layers'),
                        self::dimension('delivery_length', 'Length', 'lucide-ruler'),
                    ]),
                ]),
        ];
    }

    private static function dimension(string $name, string $label, string $icon): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->prefixIcon($icon)
            ->suffix('mm')
            ->numeric()
            ->inputMode('decimal')
            ->step(0.1)
            ->minValue(0)
            // The database stores centimeters; the form displays millimeters.
            ->formatStateUsing(fn (mixed $state): ?float => filled($state) ? round((float) $state * 10, 1) : null)
            ->dehydrateStateUsing(fn (mixed $state): ?float => filled($state) ? round((float) $state / 10, 2) : null);
    }
}
