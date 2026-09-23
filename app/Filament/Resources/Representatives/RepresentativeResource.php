<?php

namespace App\Filament\Resources\Representatives;

use App\Filament\Resources\Representatives\Pages;
use App\Models\Representative;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RepresentativeResource extends Resource
{
    protected static ?string $model = Representative::class;

    protected static string|BackedEnum|null $navigationIcon = 'lucide-map-pin';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return 'Shop';
    }

    public static function getModelLabel(): string
    {
        return 'Representative';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Representatives';
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
                                Section::make('Representative')
                                    ->icon('lucide-store')
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Name')
                                            ->required()
                                            ->prefixIcon('lucide-user-round')
                                            ->maxLength(160),

                                        Tabs::make('Translations')
                                            ->tabs(static::translationTabs()),
                                    ]),
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

                                        TextInput::make('sort')
                                            ->label('Sort order')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->prefixIcon('lucide-list-ordered'),
                                    ]),

                                Section::make('Links')
                                    ->icon('lucide-link')
                                    ->schema([
                                        TextInput::make('url')
                                            ->label('Website / primary URL')
                                            ->url()
                                            ->prefixIcon('lucide-globe')
                                            ->maxLength(2048),

                                        TextInput::make('instagram')
                                            ->label('Instagram')
                                            ->url()
                                            ->prefixIcon('lucide-instagram')
                                            ->maxLength(2048),
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
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->weight('medium'),

                TextColumn::make('country_fr')
                    ->label('Country / region')
                    ->state(fn (Representative $record): string => $record->getTranslation('country', 'fr', false) ?: '—')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereRaw("country->>'fr' ILIKE ?", ["%{$search}%"])),

                TextColumn::make('tag_fr')
                    ->label('Type')
                    ->state(fn (Representative $record): string => $record->getTranslation('tag', 'fr', false) ?: '—')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('city_fr')
                    ->label('Location')
                    ->state(fn (Representative $record): string => $record->getTranslation('city', 'fr', false) ?: '—')
                    ->color('gray'),

                TextColumn::make('sort')
                    ->label('Order')
                    ->sortable()
                    ->alignCenter(),

                IconColumn::make('active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('lucide-circle-check')
                    ->falseIcon('lucide-circle-x')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('active'),
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
            ->defaultSort('sort')
            ->defaultPaginationPageOption(24)
            ->paginationPageOptions([24, 48, 96]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRepresentatives::route('/'),
            'create' => Pages\CreateRepresentative::route('/create'),
            'edit' => Pages\EditRepresentative::route('/{record}/edit'),
        ];
    }

    protected static function translationTabs(): array
    {
        return array_map(
            fn (string $locale): Tab => Tab::make(strtoupper($locale))
                ->schema([
                    TextInput::make("country.{$locale}")
                        ->label('Country / region')
                        ->required($locale === 'fr')
                        ->prefixIcon('lucide-map')
                        ->maxLength(160),

                    TextInput::make("tag.{$locale}")
                        ->label('Type')
                        ->prefixIcon('lucide-badge-check')
                        ->maxLength(120),

                    TextInput::make("city.{$locale}")
                        ->label('Location / sales format')
                        ->prefixIcon('lucide-map-pin')
                        ->maxLength(255),

                    TextInput::make("cta.{$locale}")
                        ->label('CTA')
                        ->prefixIcon('lucide-mouse-pointer-click')
                        ->maxLength(120),
                ])
                ->columns(1),
            static::locales(),
        );
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
}
