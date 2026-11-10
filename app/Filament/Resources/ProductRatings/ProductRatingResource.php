<?php

namespace App\Filament\Resources\ProductRatings;

use App\Filament\Resources\ProductRatings\Pages;
use App\Models\Product;
use App\Models\ProductRating;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductRatingResource extends Resource
{
    protected static ?string $model = ProductRating::class;
    protected static string|BackedEnum|null $navigationIcon = 'lucide-star';
    protected static ?int $navigationSort = 3;
    protected static ?string $recordTitleAttribute = 'product_name';

    public static function getNavigationGroup(): ?string { return 'Shop'; }
    public static function getModelLabel(): string { return 'Product rating'; }
    public static function getPluralModelLabel(): string { return 'Product ratings'; }
    public static function form(Schema $schema): Schema { return $schema; }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product_name')->label('Product')->searchable(),
                TextColumn::make('product_id')->label('Product ID')->sortable(),
                TextColumn::make('rating')->label('Rating')->suffix(' / 5')->sortable()
                    ->badge()->color(fn (int $state): string => $state <= 2 ? 'danger' : ($state === 3 ? 'warning' : 'success')),
                TextColumn::make('order.order_number')->label('Order')->searchable(),
                TextColumn::make('order.user.email')->label('Customer')->searchable()->placeholder('—'),
                TextColumn::make('created_at')->label('Submitted')->dateTime('d M Y, H:i')->sortable(),
                TextColumn::make('updated_at')->label('Updated')->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('product_id')->label('Product')->searchable()
                    ->options(fn (): array => Product::withTrashed()->orderBy('id')->get()->mapWithKeys(
                        fn (Product $product): array => [
                            $product->id => ($product->getTranslation('name', 'fr', false) ?: 'Product').' #'.$product->id,
                        ],
                    )->all()),
                SelectFilter::make('rating')->options([
                    1 => '1 star', 2 => '2 stars', 3 => '3 stars', 4 => '4 stars', 5 => '5 stars',
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListProductRatings::route('/')];
    }
}
