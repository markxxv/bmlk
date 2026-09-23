<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\Pages\Pages;
use App\Models\Page;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = 'lucide-file-text';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationGroup(): ?string
    {
        return 'General';
    }

    public static function getModelLabel(): string
    {
        return 'Page';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Pages';
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
                TextColumn::make('title_fr')
                    ->label('Title')
                    ->state(fn (Page $record): string => $record->getTranslation('title', 'fr', false) ?: '—')
                    ->searchable(
                        query: fn (Builder $query, string $search): Builder =>
                            $query->whereRaw("title->>'fr' ILIKE ?", ["%{$search}%"])
                    )
                    ->weight('medium'),

                TextColumn::make('slug_fr')
                    ->label('Slug')
                    ->state(fn (Page $record): string => $record->getTranslation('slug', 'fr', false) ?: '—')
                    ->searchable(
                        query: fn (Builder $query, string $search): Builder =>
                            $query->whereRaw("slug->>'fr' ILIKE ?", ["%{$search}%"])
                    )
                    ->color('gray'),

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

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
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
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }

    protected static function translationTabs(): array
    {
        return array_map(
            fn (string $locale): Tab => Tab::make(strtoupper($locale))
                ->schema([
                    TextInput::make("title.{$locale}")
                        ->label('Title')
                        ->required($locale === 'fr')
                        ->prefixIcon('lucide-type')
                        ->maxLength(255),

                    RichEditor::make("body.{$locale}")
                        ->label('Body')
                        ->columnSpanFull(),

                    Section::make('SEO')
                        ->icon('lucide-search')
                        ->collapsible()
                        ->collapsed()
                        ->schema([
                            TextInput::make("slug.{$locale}")
                                ->label('Slug')
                                ->prefixIcon('lucide-link')
                                ->helperText('Generated from the title on creation when empty. It will not change automatically later.')
                                ->maxLength(255),

                            TextInput::make("meta_title.{$locale}")
                                ->label('Meta title')
                                ->maxLength(255),

                            Textarea::make("meta_description.{$locale}")
                                ->label('Meta description')
                                ->rows(4)
                                ->maxLength(1000),
                        ])
                        ->columnSpanFull(),
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
