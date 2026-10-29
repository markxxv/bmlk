<?php

namespace App\Filament\Resources\Countries;

use App\Filament\Resources\Countries\Pages;
use App\Models\Country;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CountryResource extends Resource
{
    protected static ?string $model = Country::class;

    protected static string|BackedEnum|null $navigationIcon = 'lucide-globe-2';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return 'Shop';
    }

    public static function getModelLabel(): string
    {
        return 'Pays';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Pays de livraison';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Pays de livraison')
                ->icon('lucide-globe')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Nom')
                        ->prefixIcon('lucide-map-pin')
                        ->required()
                        ->maxLength(150),
                    TextInput::make('code')
                        ->label('Code ISO')
                        ->prefixIcon('lucide-hash')
                        ->minLength(2)
                        ->maxLength(2)
                        ->rule('regex:/^[A-Z]{2}$/')
                        ->unique(ignoreRecord: true)
                        ->required()
                        ->disabled(fn (?Country $record): bool => $record !== null),
                    TextInput::make('sort')
                        ->label('Ordre')
                        ->prefixIcon('lucide-list-ordered')
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->default(1000)
                        ->required(),
                    Toggle::make('active')
                        ->label('Livraison autorisée')
                        ->default(true)
                        ->onColor('success')
                        ->offColor('danger'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Pays')
                    ->weight('medium')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label('ISO')
                    ->color('gray')
                    ->searchable(),
                ToggleColumn::make('active')
                    ->label('Actif')
                    ->sortable(),
                TextColumn::make('sort')
                    ->label('Ordre')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('active')->label('Disponible'),
            ])
            ->recordActions([
                EditAction::make()->icon('lucide-pencil'),
            ])
            ->reorderable('sort')
            ->defaultSort('sort')
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCountries::route('/'),
            'create' => Pages\CreateCountry::route('/create'),
            'edit' => Pages\EditCountry::route('/{record}/edit'),
        ];
    }
}
