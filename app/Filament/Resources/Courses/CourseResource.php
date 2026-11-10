<?php

namespace App\Filament\Resources\Courses;

use App\Filament\Resources\Courses\Pages;
use App\Models\Course;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
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

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static string|BackedEnum|null $navigationIcon = 'lucide-graduation-cap';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return 'Education';
    }

    public static function getModelLabel(): string
    {
        return 'Course';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Courses';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'xl' => 3])
                ->schema([
                    Grid::make(1)
                        ->schema([
                            Section::make('Content')
                                ->icon('lucide-languages')
                                ->schema([Tabs::make('Translations')->tabs(static::translationTabs())]),

                            Section::make('Learning access (private)')
                                ->icon('lucide-lock')
                                ->description('Shown only in the account of customers who bought the course.')
                                ->schema([
                                    TextInput::make('telegram_channel_url')
                                        ->label('Telegram group with the learning materials')
                                        ->url()
                                        ->prefixIcon('lucide-graduation-cap')
                                        ->maxLength(2048),

                                    TextInput::make('telegram_chat_url')
                                        ->label('Course chat (discussion)')
                                        ->url()
                                        ->prefixIcon('lucide-messages-square')
                                        ->maxLength(2048),
                                ]),
                        ])
                        ->columnSpan(['default' => 1, 'xl' => 2]),

                    Grid::make(1)
                        ->schema([
                            Section::make('Course')
                                ->icon('lucide-book-open')
                                ->schema([
                                    Toggle::make('active')->label('Active')->default(true),

                                    Select::make('mode')
                                        ->label('Format')
                                        ->options(['online' => 'Online', 'live' => 'Live (in person)'])
                                        ->default('online')
                                        ->required()
                                        ->native(false),

                                    SpatieMediaLibraryFileUpload::make('image')
                                        ->label('Cover image')
                                        ->collection('image')
                                        ->disk('public')
                                        ->image()
                                        ->imageEditor(),
                                ]),

                            Section::make('Price')
                                ->icon('lucide-euro')
                                ->schema([
                                    TextInput::make('price')
                                        ->label('Price')
                                        ->numeric()
                                        ->minValue(0)
                                        ->step(0.01)
                                        ->prefix('€')
                                        ->helperText('Online courses with a price are sold through Stripe in one click. Empty = “request information”.'),
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
                TextColumn::make('name_fr')
                    ->label('Course')
                    ->state(fn (Course $record): string => $record->getTranslation('name', 'fr', false) ?: '—')
                    ->searchable(query: fn ($query, string $search) => $query->whereRaw("name->>'fr' ILIKE ?", ["%{$search}%"]))
                    ->weight('medium'),
                TextColumn::make('mode')->label('Format')->badge()->color('gray'),
                TextColumn::make('price')->label('Price')->money('EUR')->placeholder('—')->sortable(),
                TextColumn::make('purchases_count')->label('Sold')
                    ->counts(['purchases' => fn ($query) => $query->where('status', 'paid')])
                    ->alignCenter(),
                IconColumn::make('telegram_channel_url')->label('Materials')->boolean()
                    ->getStateUsing(fn (Course $record): bool => filled($record->telegram_channel_url)),
                IconColumn::make('telegram_chat_url')->label('Chat')->boolean()
                    ->getStateUsing(fn (Course $record): bool => filled($record->telegram_chat_url)),
                IconColumn::make('active')->label('Active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('mode')->options(['online' => 'Online', 'live' => 'Live']),
                TernaryFilter::make('active'),
            ])
            ->recordActions([EditAction::make()->icon('lucide-pencil')])
            ->reorderable('sort')
            ->defaultSort('sort');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourses::route('/'),
            'create' => Pages\CreateCourse::route('/create'),
            'edit' => Pages\EditCourse::route('/{record}/edit'),
        ];
    }

    protected static function translationTabs(): array
    {
        return array_map(
            fn (string $locale): Tab => Tab::make(strtoupper($locale))
                ->schema([
                    TextInput::make("tag.{$locale}")
                        ->label('Pre header')
                        ->maxLength(255),

                    TextInput::make("name.{$locale}")
                        ->label('Name')
                        ->required($locale === 'fr')
                        ->maxLength(255),

                    Textarea::make("description.{$locale}")->label('Description')->rows(4),

                    Repeater::make("points.{$locale}")
                        ->label('Key points')
                        ->simple(TextInput::make('point')->maxLength(255))
                        ->addActionLabel('Add point')
                        ->reorderable(),

                    FileUpload::make("info_pdf.{$locale}")
                        ->label('Public info PDF')
                        ->disk('public')
                        ->directory('courses/pdf')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(20480)
                        ->downloadable()
                        ->openable(),
                ])
                ->columns(1),
            static::locales(),
        );
    }

    protected static function locales(): array
    {
        return config('app.supported_locales', ['fr', 'en', 'es']);
    }
}
