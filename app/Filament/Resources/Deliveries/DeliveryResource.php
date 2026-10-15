<?php

namespace App\Filament\Resources\Deliveries;

use App\Filament\Resources\Deliveries\Pages;
use App\Models\Delivery;
use App\Models\Order;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class DeliveryResource extends Resource
{
    protected static ?string $model = Delivery::class;

    protected static string|BackedEnum|null $navigationIcon = 'lucide-truck';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return 'Shop';
    }

    public static function getModelLabel(): string
    {
        return 'Delivery zone';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Deliveries';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'xl' => 3])
                ->schema([
                    Grid::make(1)
                        ->schema([
                            Section::make('Destination')
                                ->icon('lucide-map-pinned')
                                ->columns(2)
                                ->schema([
                                    TextInput::make('name')
                                        ->label('Country / region')
                                        ->placeholder('France')
                                        ->prefixIcon('lucide-globe')
                                        ->required()
                                        ->maxLength(120)
                                        ->columnSpanFull(),

                                    TextInput::make('code')
                                        ->label('Zone code')
                                        ->placeholder('fr')
                                        ->prefixIcon('lucide-hash')
                                        ->required()
                                        ->maxLength(20)
                                        ->unique(ignoreRecord: true)
                                        ->rule('regex:/^[a-z0-9-]+$/')
                                        ->disabled(fn (?Delivery $record): bool => $record !== null)
                                        ->helperText('Used by checkout. Cannot change after creation.'),
                                ]),

                            Section::make('Shipping')
                                ->icon('lucide-package')
                                ->columns(2)
                                ->schema([
                                    TextInput::make('carrier')
                                        ->label('Carrier')
                                        ->placeholder('Colissimo')
                                        ->prefixIcon('lucide-truck')
                                        ->maxLength(120),

                                    TextInput::make('delay')
                                        ->label('Estimated delivery')
                                        ->placeholder('2 à 4 jours ouvrés')
                                        ->prefixIcon('lucide-clock')
                                        ->maxLength(120),

                                    TextInput::make('price')
                                        ->label('Shipping price')
                                        ->prefixIcon('lucide-wallet')
                                        ->suffix('€')
                                        ->numeric()
                                        ->step(0.01)
                                        ->minValue(0)
                                        ->required(),

                                    TextInput::make('free_from')
                                        ->label('Free shipping from')
                                        ->prefixIcon('lucide-gift')
                                        ->suffix('€')
                                        ->numeric()
                                        ->step(0.01)
                                        ->minValue(0)
                                        ->helperText('Leave empty when free shipping is not offered.'),
                                ]),
                        ])
                        ->columnSpan(['default' => 1, 'xl' => 2]),

                    Grid::make(1)
                        ->schema([
                            Section::make('Availability')
                                ->icon('lucide-settings-2')
                                ->schema([
                                    Toggle::make('active')
                                        ->label('Active')
                                        ->helperText('Only active zones appear at checkout.')
                                        ->default(true)
                                        ->onIcon('lucide-check')
                                        ->offIcon('lucide-x')
                                        ->onColor('success')
                                        ->offColor('danger'),

                                    TextInput::make('sort')
                                        ->label('Display order')
                                        ->prefixIcon('lucide-list-ordered')
                                        ->numeric()
                                        ->integer()
                                        ->minValue(0)
                                        ->maxValue(65535)
                                        ->default(0)
                                        ->required()
                                        ->helperText('You can also drag zones to reorder them in the list.'),
                                ]),
                        ])
                        ->columnSpan(['default' => 1, 'xl' => 1]),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->badge()
                    ->color('gray')
                    ->weight('medium')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Country / region')
                    ->weight('medium')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('carrier')
                    ->label('Carrier')
                    ->icon('lucide-truck')
                    ->color('gray')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('delay')
                    ->label('Delivery time')
                    ->icon('lucide-clock')
                    ->color('gray')
                    ->placeholder('—'),

                TextColumn::make('price')
                    ->label('Price')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('free_from')
                    ->label('Free from')
                    ->money('EUR')
                    ->placeholder('—')
                    ->sortable(),

                IconColumn::make('active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('lucide-circle-check')
                    ->falseIcon('lucide-circle-x')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                TextColumn::make('sort')
                    ->label('Order')
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('active')
                    ->label('Availability'),
            ])
            ->recordActions([
                EditAction::make()->icon('lucide-pencil'),
                DeleteAction::make()
                    ->icon('lucide-trash-2')
                    ->visible(fn (Delivery $record): bool => ! Order::query()
                        ->where('delivery_code', $record->code)
                        ->exists()),
            ])
            ->reorderable('sort')
            ->defaultSort('sort')
            ->defaultPaginationPageOption(24)
            ->paginationPageOptions([24, 48, 96]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeliveries::route('/'),
            'create' => Pages\CreateDelivery::route('/create'),
            'edit' => Pages\EditDelivery::route('/{record}/edit'),
        ];
    }
}
