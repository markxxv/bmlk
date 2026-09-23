<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages;
use App\Models\Category;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = 'lucide-folder';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return 'Shop';
    }

    public static function getModelLabel(): string
    {
        return 'Category';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Categories';
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
                                Section::make('Structure')
                                    ->icon('lucide-folder-tree')
                                    ->schema([
                                        Select::make('parent_id')
                                            ->label('Parent category')
                                            ->options(fn (?Category $record): array => static::parentOptions($record))
                                            ->searchable()
                                            ->preload()
                                            ->native(false)
                                            ->prefixIcon('lucide-folder')
                                            ->placeholder('Root category'),
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
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('name_fr')
                    ->label('Name')
                    ->state(fn (Category $record): string => $record->getTranslation('name', 'fr', false) ?: '—')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereRaw("name->>'fr' ILIKE ?", ["%{$search}%"]))
                    ->weight('medium'),

                TextColumn::make('parent_name')
                    ->label('Parent')
                    ->state(fn (Category $record): string => $record->parent?->getTranslation('name', 'fr', false) ?: '—')
                    ->color('gray'),

                TextColumn::make('products_count')
                    ->label('Products')
                    ->counts('products')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('parent_id')
                    ->label('Parent category')
                    ->options(fn (): array => static::parentOptions()),
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
            ->defaultSort('id');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
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

                    TextInput::make("slug.{$locale}")
                        ->label('URL slug')
                        ->required($locale === 'fr')
                        ->prefixIcon('lucide-link')
                        ->maxLength(255),

                    Textarea::make("description.{$locale}")
                        ->label('Description')
                        ->rows(5),

                    Textarea::make("use.{$locale}")
                        ->label('Usage')
                        ->rows(4),

                    TextInput::make("price_label.{$locale}")
                        ->label('Price label')
                        ->prefixIcon('lucide-euro')
                        ->placeholder('from 15 €')
                        ->maxLength(100),

                    TextInput::make("meta_title.{$locale}")
                        ->label('Meta title')
                        ->prefixIcon('lucide-heading')
                        ->maxLength(70),

                    Textarea::make("meta_description.{$locale}")
                        ->label('Meta description')
                        ->rows(3)
                        ->maxLength(170),
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

    protected static function parentOptions(?Category $record = null): array
    {
        return Category::query()
            ->when($record, fn (Builder $query): Builder => $query->whereKeyNot($record->getKey()))
            ->orderByRaw("name->>'fr'")
            ->get()
            ->mapWithKeys(fn (Category $category): array => [
                $category->id => $category->getTranslation('name', 'fr', false) ?: "#{$category->id}",
            ])
            ->all();
    }
}