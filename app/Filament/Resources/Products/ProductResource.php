<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages;
use App\Models\Category;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = 'lucide-package';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return 'Shop';
    }

    public static function getModelLabel(): string
    {
        return 'Product';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Products';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'default' => 1,
                    'xl' => 3,
                    '2xl' => 3,
                ])
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                Section::make('Content')
                                    ->icon('lucide-languages')
                                    ->schema([
                                        Tabs::make('Translations')
                                            ->tabs(static::translationTabs()),
                                    ]),

                                Section::make('Media')
                                    ->icon('lucide-images')
                                    ->schema([
                                        SpatieMediaLibraryFileUpload::make('images')
                                            ->label('Product images')
                                            ->collection('images')
                                            ->conversion('thumb')
                                            ->image()
                                            ->multiple()
                                            ->reorderable()
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Product data')
                                    ->icon('lucide-list')
                                    ->schema([
                                        TagsInput::make('claims')
                                            ->label('Claims')
                                            ->placeholder('HEMA-free')
                                            ->columnSpanFull(),

                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('size.raw')
                                                    ->label('Size label')
                                                    ->placeholder('30 ml'),
                                                TextInput::make('size.volume_ml')
                                                    ->label('Volume')
                                                    ->numeric()
                                                    ->suffix('ml'),
                                                TextInput::make('size.weight_g')
                                                    ->label('Weight')
                                                    ->numeric()
                                                    ->suffix('g'),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('measurements.weight_kg')
                                                    ->label('Shipping weight')
                                                    ->numeric()
                                                    ->suffix('kg'),
                                                TextInput::make('measurements.dimensions_cm_raw')
                                                    ->label('Dimensions')
                                                    ->placeholder('18 × 3 cm'),
                                            ]),

                                        Repeater::make('options')
                                            ->label('Product options')
                                            ->schema([
                                                ToggleButtons::make('type')
                                                    ->label('Option type')
                                                    ->options(static::productOptionTypeOptions())
                                                    ->icons([
                                                        'diameter' => 'lucide-ruler',
                                                        'pack_quantity' => 'lucide-package',
                                                    ])
                                                    ->default('diameter')
                                                    ->grouped()
                                                    ->required()
                                                    ->columnSpanFull(),

                                                Repeater::make('values')
                                                    ->label('Values')
                                                    ->table([
                                                        TableColumn::make('Value')
                                                            ->width('45%'),
                                                        TableColumn::make('Quantity')
                                                            ->width('20%'),
                                                        TableColumn::make('Price')
                                                            ->width('25%'),
                                                    ])
                                                    ->compact()
                                                    ->schema([
                                                        TextInput::make('value'),
                                                        TextInput::make('quantity')
                                                            ->numeric()
                                                            ->minValue(1),
                                                        TextInput::make('price')
                                                            ->numeric()
                                                            ->inputMode('decimal')
                                                            ->minValue(0)
                                                            ->prefix('€'),
                                                    ])
                                                    ->defaultItems(0)
                                                    ->addActionLabel('Add value')
                                                    ->reorderable()
                                                    ->columnSpanFull(),
                                            ])
                                            ->dehydrateStateUsing(fn (mixed $state): ?array => static::optionsForStorage($state))
                                            ->itemLabel(fn (array $state): ?string => static::productOptionTypeOptions()[data_get($state, 'type')] ?? 'Option')
                                            ->defaultItems(0)
                                            ->addActionLabel('Add option')
                                            ->collapsible()
                                            ->reorderable()
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1),
                            ])
                            ->columnSpan([
                                'default' => 1,
                                'xl' => 2,
                                '2xl' => 2,
                            ]),

                        Grid::make(1)
                            ->schema([
                                   Section::make('Visibility')
                                    ->icon('lucide-eye')
                                    ->schema([
                                        Toggle::make('active')
                                            ->label('Active')
                                            ->default(true)
                                            ->onIcon('lucide-check')
                                            ->offIcon('lucide-x')
                                            ->onColor('success')
                                            ->offColor('danger'),
                                        Toggle::make('featured')
                                            ->label('Featured')
                                            ->onIcon('lucide-star')
                                            ->offIcon('lucide-star-off'),
                                        Toggle::make('is_new')
                                            ->label('New')
                                            ->onIcon('lucide-sparkles')
                                            ->offIcon('lucide-circle'),
                                    ]),
                          
                                Section::make('Pricing')
                                    ->icon('lucide-euro')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('price')
                                                    ->label('Price')
                                                    ->numeric()
                                                    ->inputMode('decimal')
                                                    ->prefix('€'),
                                                TextInput::make('compare_at_price')
                                                    ->label('Compare at')
                                                    ->numeric()
                                                    ->inputMode('decimal')
                                                    ->prefix('€'),
                                                TextInput::make('price_min')
                                                    ->label('Minimum')
                                                    ->numeric()
                                                    ->inputMode('decimal')
                                                    ->prefix('€'),
                                                TextInput::make('price_max')
                                                    ->label('Maximum')
                                                    ->numeric()
                                                    ->inputMode('decimal')
                                                    ->prefix('€'),
                                            ]),
                                    ]),

                                Section::make('Catalog')
                                    ->icon('lucide-package')
                                    ->schema([
                                        Select::make('category_id')
                                            ->label('Category')
                                            ->options(fn (): array => static::categoryOptions())
                                            ->searchable()
                                            ->preload()
                                            ->native(false)
                                            ->prefixIcon('lucide-folder')
                                            ->required(),

                                        // TextInput::make('sku')
                                        //     ->label('SKU')
                                        //     ->prefixIcon('lucide-barcode')
                                        //     ->maxLength(120),
                                    ]),


                            ])
                            ->columnSpan([
                                'default' => 1,
                                'xl' => 1,
                                '2xl' => 1,
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('images')
                    ->label('')
                    ->collection('images')
                    ->conversion('thumb')
                    ->filterMediaUsing(fn (Collection $media): Collection => $media->take(1))
                    ->square()
                    ->size(90),

                TextColumn::make('name_fr')
                    ->label('Name')
                    ->state(fn (Product $record): string => $record->getTranslation('name', 'fr', false) ?: '—')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereRaw("name->>'fr' ILIKE ?", ["%{$search}%"]))
                    ->weight('medium'),

                // TextColumn::make('slug_fr')
                //     ->label('Slug')
                //     ->state(fn (Product $record): string => $record->getTranslation('slug', 'fr', false) ?: '—')
                //     ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereRaw("slug->>'fr' ILIKE ?", ["%{$search}%"]))
                //     ->color('gray')
                //     ->toggleable(),

                TextColumn::make('category_name')
                    ->label('Category')
                    ->state(fn (Product $record): string => $record->category?->getTranslation('name', 'fr', false) ?: '—')
                    ->badge()
                    ->color('gray'),

                // TextColumn::make('sku')
                //     ->label('SKU')
                //     ->searchable()
                //     ->placeholder('—')
                //     ->toggleable(),

                TextColumn::make('price_display')
                    ->label('Price')
                    ->state(fn (Product $record): string => static::priceLabel($record))
                    ->alignEnd(),

                IconColumn::make('active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('lucide-circle-check')
                    ->falseIcon('lucide-circle-x')
                    ->trueColor('success')
                    ->falseColor('danger'),

                IconColumn::make('featured')
                    ->label('Featured')
                    ->boolean()
                    ->trueIcon('lucide-star')
                    ->falseIcon('lucide-minus')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_new')
                    ->label('New')
                    ->boolean()
                    ->trueIcon('lucide-sparkles')
                    ->falseIcon('lucide-minus')
                    ->trueColor('info')
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(fn (): array => static::categoryOptions()),
                TernaryFilter::make('active'),
                TernaryFilter::make('featured'),
                TernaryFilter::make('is_new')
                    ->label('New'),
            ])
            ->recordActions([
                EditAction::make()
                    ->icon('lucide-pencil'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->icon('lucide-trash-2'),
                ]),
            ])
            ->defaultSort('id', 'desc')
            ->defaultPaginationPageOption(24)
            ->paginationPageOptions([24, 48, 96]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    protected static function translationTabs(): array
    {
        return array_map(
            fn (string $locale): Tab => Tab::make(strtoupper($locale))
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make("name.{$locale}")
                                ->label('Name')
                                ->required($locale === 'fr')
                                ->prefixIcon('lucide-type')
                                ->maxLength(255)
                                    ->columnSpanFull(),
                        ]),


                    Textarea::make("description.{$locale}")
                        ->label('Description')
                        ->rows(5)
                        ->columnSpanFull(),

                    TextInput::make("tag.{$locale}")
                        ->label('Tag')
                        ->prefixIcon('lucide-tag')
                        ->maxLength(255),

                    Repeater::make("details.{$locale}")
                        ->label('Details')
                        ->schema([
                            TextInput::make('title')
                                ->required()
                                ->maxLength(255),
                            Textarea::make('body')
                                ->rows(4)
                                ->required(),
                        ])
                        ->columns(1)
                        ->collapsible()
                        ->reorderable()
                        ->columnSpanFull(),

                    TagsInput::make("contents.{$locale}")
                        ->label('Set contents')
                        ->placeholder('Add item')
                        ->columnSpanFull(),

                         Section::make('SEO')
                    ->icon('lucide-search')
                    ->schema([
                        TextInput::make("slug.{$locale}")
                            ->label('Slug')
                            ->prefixIcon('lucide-link')
                            ->helperText('Generated from the name on creation when empty. It will not change automatically later.')
                            ->maxLength(190),

                        TextInput::make("meta_title.{$locale}")
                            ->label('Meta title')
                            ->prefixIcon('lucide-heading'),

                        Textarea::make("meta_description.{$locale}")
                            ->label('Meta description')
                            ->rows(3),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull(),
                ])
                ->columns(1),
            static::locales(),
        );
    }

    public static function optionsForForm(mixed $options): array
    {
        if (! is_array($options)) {
            return [];
        }

        return collect($options)
            ->filter(fn (mixed $option): bool => is_array($option))
            ->map(function (array $option): array {
                $option['type'] = data_get($option, 'id');

                $option['values'] = collect($option['values'] ?? [])
                    ->map(function (mixed $value): array {
                        if (is_array($value)) {
                            return [
                                'value' => null,
                                'quantity' => data_get($value, 'quantity'),
                                'price' => data_get($value, 'price.amount'),
                            ];
                        }

                        return [
                            'value' => $value,
                            'quantity' => null,
                            'price' => null,
                        ];
                    })
                    ->values()
                    ->all();

                return [
                    'type' => $option['type'] ?? null,
                    'values' => $option['values'],
                ];
            })
            ->values()
            ->all();
    }

    protected static function optionsForStorage(mixed $options): ?array
    {
        if (! is_array($options) || $options === []) {
            return null;
        }

        $normalized = collect($options)
            ->filter(fn (mixed $option): bool => is_array($option))
            ->map(function (array $option): array {
                $values = collect($option['values'] ?? [])
                    ->filter(fn (mixed $value): bool => is_array($value))
                    ->map(function (array $value): mixed {
                        $plainValue = data_get($value, 'value');
                        $quantity = data_get($value, 'quantity');
                        $price = data_get($value, 'price');

                        if (filled($quantity) || filled($price)) {
                            return [
                                'quantity' => filled($quantity) ? (int) $quantity : null,
                                'price' => [
                                    'currency' => 'EUR',
                                    'amount' => filled($price) ? (float) $price : null,
                                ],
                            ];
                        }

                        return filled($plainValue) ? $plainValue : null;
                    })
                    ->filter(fn (mixed $value): bool => $value !== null)
                    ->values()
                    ->all();

                $type = data_get($option, 'type');
                $definition = static::productOptionTypes()[$type] ?? null;

                if (! $definition) {
                    return null;
                }

                return [
                    'id' => $type,
                    'label' => $definition['label'],
                    'values' => $values,
                    'source' => 'filament',
                ];
            })
            ->filter(fn (?array $option): bool => $option !== null && $option['values'] !== [])
            ->values()
            ->all();

        return $normalized === [] ? null : $normalized;
    }

    protected static function productOptionTypes(): array
    {
        return [
            'diameter' => [
                'label' => [
                    'FR' => 'Diamètre',
                    'EN' => 'Diameter',
                    'RO' => 'Diametru',
                ],
            ],
            'pack_quantity' => [
                'label' => [
                    'FR' => 'Conditionnement',
                    'EN' => 'Pack size',
                    'RO' => 'Ambalaj',
                ],
            ],
        ];
    }

    protected static function productOptionTypeOptions(): array
    {
        return collect(static::productOptionTypes())
            ->mapWithKeys(fn (array $definition, string $key): array => [
                $key => $definition['label']['FR'],
            ])
            ->all();
    }

    protected static function locales(): array
    {
        $locales = config('app.supported_locales', ['fr', 'en', 'ro']);

        if (! is_array($locales) || $locales === []) {
            return ['fr', 'en', 'ro'];
        }

        return array_is_list($locales)
            ? array_values($locales)
            : array_keys($locales);
    }

    protected static function categoryOptions(): array
    {
        return Category::query()
            ->orderByRaw("name->>'fr'")
            ->get()
            ->mapWithKeys(fn (Category $category): array => [
                $category->id => $category->getTranslation('name', 'fr', false) ?: "#{$category->id}",
            ])
            ->all();
    }

    protected static function priceLabel(Product $product): string
    {
        if ($product->price !== null) {
            return number_format((float) $product->price, 2, ',', ' ').' €';
        }

        if ($product->price_min !== null || $product->price_max !== null) {
            $min = $product->price_min !== null
                ? number_format((float) $product->price_min, 2, ',', ' ')
                : '—';
            $max = $product->price_max !== null
                ? number_format((float) $product->price_max, 2, ',', ' ')
                : '—';

            return "{$min} – {$max} €";
        }

        return '—';
    }
}
