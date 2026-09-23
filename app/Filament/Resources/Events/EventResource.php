<?php

namespace App\Filament\Resources\Events;

use App\Filament\Resources\Events\Pages;
use App\Models\Event;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static string|BackedEnum|null $navigationIcon = 'lucide-calendar-days';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return 'General';
    }

    public static function getModelLabel(): string
    {
        return 'Event';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Events';
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

                                Section::make('Date & location')
                                    ->icon('lucide-map-pin')
                                    ->schema([
                                        Grid::make([
                                            'default' => 1,
                                            'md' => 2,
                                        ])
                                            ->schema([
                                                DateTimePicker::make('starts_at')
                                                    ->label('Starts at')
                                                    ->native(false)
                                                    ->seconds(false),

                                                DateTimePicker::make('ends_at')
                                                    ->label('Ends at')
                                                    ->native(false)
                                                    ->seconds(false)
                                                    ->afterOrEqual('starts_at'),
                                            ])
                                            ->columnSpanFull(),

                                        Grid::make([
                                            'default' => 1,
                                            'md' => 2,
                                        ])
                                            ->schema([
                                                TextInput::make('country')
                                                    ->label('Country')
                                                    ->prefixIcon('lucide-map')
                                                    ->maxLength(120),

                                                TextInput::make('city')
                                                    ->label('City')
                                                    ->prefixIcon('lucide-map-pin')
                                                    ->maxLength(120),
                                            ])
                                            ->columnSpanFull(),

                                        TextInput::make('address')
                                            ->label('Address')
                                            ->prefixIcon('lucide-navigation')
                                            ->maxLength(255),
                                    ]),
                            ])
                            ->columnSpan([
                                'default' => 1,
                                'xl' => 2,
                                '2xl' => 2,
                            ]),

                        Grid::make(1)
                            ->schema([
                                Section::make('Event')
                                    ->icon('lucide-calendar-check')
                                    ->schema([
                                        Toggle::make('active')
                                            ->label('Active')
                                            ->default(true)
                                            ->onIcon('lucide-check')
                                            ->offIcon('lucide-x')
                                            ->onColor('success')
                                            ->offColor('danger'),

                                        Select::make('type')
                                            ->label('Type')
                                            ->options(static::typeOptions())
                                            ->required()
                                            ->native(false)
                                            ->prefixIcon('lucide-tags'),

                                        TextInput::make('sort')
                                            ->label('Sort order')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->prefixIcon('lucide-list-ordered'),
                                    ]),

                                Section::make('Tickets')
                                    ->icon('lucide-ticket')
                                    ->schema([
                                        TextInput::make('price')
                                            ->label('Price')
                                            ->numeric()
                                            ->minValue(0)
                                            ->step(0.01)
                                            ->prefix('€'),

                                        TextInput::make('ticket_url')
                                            ->label('External ticket URL')
                                            ->url()
                                            ->prefixIcon('lucide-external-link')
                                            ->maxLength(2048)
                                            ->helperText('Leave empty when tickets are sold directly by BLACK MILK.'),
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
                TextColumn::make('name_fr')
                    ->label('Event')
                    ->state(fn (Event $record): string => $record->getTranslation('name', 'fr', false) ?: '—')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereRaw("name->>'fr' ILIKE ?", ["%{$search}%"]))
                    ->weight('medium'),

                TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => static::typeOptions()[$state] ?? $state)
                    ->badge()
                    ->color('gray'),

                TextColumn::make('starts_at')
                    ->label('Starts')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('city')
                    ->label('City')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('price')
                    ->label('Price')
                    ->money('EUR')
                    ->placeholder('—')
                    ->sortable(),

                IconColumn::make('ticket_url')
                    ->label('External')
                    ->boolean()
                    ->getStateUsing(fn (Event $record): bool => filled($record->ticket_url))
                    ->trueIcon('lucide-external-link')
                    ->falseIcon('lucide-shopping-bag')
                    ->trueColor('info')
                    ->falseColor('gray'),

                IconColumn::make('active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('lucide-circle-check')
                    ->falseIcon('lucide-circle-x')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('sort')
                    ->label('Order')
                    ->sortable()
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(static::typeOptions()),

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
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }

    protected static function translationTabs(): array
    {
        return array_map(
            fn (string $locale): Tab => Tab::make(strtoupper($locale))
                ->schema([
                    TextInput::make("name.{$locale}")
                        ->label('Name')
                        ->required($locale === 'fr')
                        ->prefixIcon('lucide-type')
                        ->maxLength(255),

                    TextInput::make("url.{$locale}")
                        ->label('URL slug')
                        ->prefixIcon('lucide-link')
                        ->maxLength(255)
                        ->helperText('Reserved for future event detail pages.'),

                    Textarea::make("description.{$locale}")
                        ->label('Description')
                        ->rows(6),
                ])
                ->columns(1),
            static::locales(),
        );
    }

    protected static function typeOptions(): array
    {
        return [
            'training' => 'Training',
            'workshop' => 'Workshop',
            'international_tour' => 'International tour',
            'event' => 'Event',
        ];
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
