<?php

namespace App\Filament\Resources\ShippingBoxes;

use App\Filament\Resources\ShippingBoxes\Pages;
use App\Models\ShippingBox;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ShippingBoxResource extends Resource
{
    protected static ?string $model = ShippingBox::class;

    protected static string|BackedEnum|null $navigationIcon = 'lucide-package-open';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return 'Shop';
    }

    public static function getModelLabel(): string
    {
        return 'Shipping box';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Shipping boxes';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'xl' => 3])
                ->schema([
                    Grid::make(1)->schema([
                        Section::make('Identification')
                            ->icon('lucide-package')
                            ->columns(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Box name')
                                    ->prefixIcon('lucide-package')
                                    ->required()
                                    ->maxLength(120),
                                TextInput::make('code')
                                    ->label('Code')
                                    ->prefixIcon('lucide-hash')
                                    ->required()
                                    ->maxLength(40)
                                    ->unique(ignoreRecord: true)
                                    ->rule('regex:/^[a-z0-9-]+$/')
                                    ->disabled(fn (?ShippingBox $record): bool => $record !== null),
                            ]),

                        Section::make('Internal dimensions')
                            ->description('Usable space inside the box, in millimetres.')
                            ->icon('lucide-box')
                            ->columns(3)
                            ->schema([
                                static::dimension('inner_length_cm', 'Length'),
                                static::dimension('inner_width_cm', 'Width'),
                                static::dimension('inner_height_cm', 'Height'),
                            ]),

                        Section::make('External dimensions')
                            ->description('Outside measurements used for carrier pricing, in millimetres.')
                            ->icon('lucide-ruler')
                            ->columns(3)
                            ->schema([
                                static::dimension('outer_length_cm', 'Length'),
                                static::dimension('outer_width_cm', 'Width'),
                                static::dimension('outer_height_cm', 'Height'),
                            ]),

                        Section::make('Weight')
                            ->icon('lucide-weight')
                            ->columns(2)
                            ->schema([
                                TextInput::make('tare_weight_g')
                                    ->label('Empty box + protective filling')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(1)
                                    ->suffix('g')
                                    ->required(fn (Get $get): bool => (bool) $get('active')),
                                TextInput::make('max_weight_g')
                                    ->label('Maximum contents weight')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(1)
                                    ->suffix('g')
                                    ->helperText('Optional load limit.'),
                            ]),
                    ])->columnSpan(['default' => 1, 'xl' => 2]),

                    Section::make('Availability')
                        ->icon('lucide-settings-2')
                        ->schema([
                            Toggle::make('active')
                                ->label('Ready for shipping')
                                ->default(false)
                                ->live()
                                ->helperText('Enable only after all dimensions and the packaging weight are measured.')
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
                                ->required(),
                        ])->columnSpan(['default' => 1, 'xl' => 1]),
                ])->columnSpanFull(),
        ]);
    }

    private static function dimension(string $field, string $label): TextInput
    {
        return TextInput::make($field)
            ->label($label)
            ->numeric()
            ->step(0.1)
            ->minValue(0.1)
            ->suffix('mm')
            ->required(fn (Get $get): bool => (bool) $get('active'))
            ->formatStateUsing(fn (mixed $state): ?float => $state === null ? null : round((float) $state * 10, 1))
            ->dehydrateStateUsing(fn (mixed $state): ?float => $state === null || $state === '' ? null : round((float) $state / 10, 2));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Box')->weight('medium')->searchable()->sortable(),
                TextColumn::make('code')->label('Code')->color('gray')->searchable(),
                TextColumn::make('inner_length_cm')->label('Inside (mm)')
                    ->formatStateUsing(fn (ShippingBox $record): string => static::dimensionsLabel($record, 'inner')),
                TextColumn::make('outer_length_cm')->label('Outside (mm)')
                    ->formatStateUsing(fn (ShippingBox $record): string => static::dimensionsLabel($record, 'outer')),
                TextColumn::make('tare_weight_g')->label('Packaging')->suffix(' g')->placeholder('—'),
                TextColumn::make('max_weight_g')->label('Max. contents')->suffix(' g')->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('active')->label('Ready')->boolean()
                    ->trueColor('success')->falseColor('gray')->sortable(),
                TextColumn::make('sort')->label('Order')->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('active')->label('Ready for shipping'),
            ])
            ->recordActions([
                EditAction::make()->icon('lucide-pencil'),
                DeleteAction::make()->icon('lucide-trash-2'),
            ])
            ->reorderable('sort')
            ->defaultSort('sort')
            ->defaultPaginationPageOption(24)
            ->paginationPageOptions([24, 48, 96]);
    }

    private static function dimensionsLabel(ShippingBox $box, string $prefix): string
    {
        $values = collect(['length', 'width', 'height'])
            ->map(fn (string $side): ?string => $box->{$prefix.'_'.$side.'_cm'} === null
                ? null
                : (string) (float) ((float) $box->{$prefix.'_'.$side.'_cm'} * 10));

        return $values->contains(null) ? '—' : $values->implode(' × ');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShippingBoxes::route('/'),
            'create' => Pages\CreateShippingBox::route('/create'),
            'edit' => Pages\EditShippingBox::route('/{record}/edit'),
        ];
    }
}
