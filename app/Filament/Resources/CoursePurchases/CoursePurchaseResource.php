<?php

namespace App\Filament\Resources\CoursePurchases;

use App\Filament\Resources\CoursePurchases\Pages;
use App\Models\CoursePurchase;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CoursePurchaseResource extends Resource
{
    protected static ?string $model = CoursePurchase::class;

    protected static string|BackedEnum|null $navigationIcon = 'lucide-receipt';

    protected static ?int $navigationSort = 6;

    public static function getNavigationGroup(): ?string
    {
        return 'Education';
    }

    public static function getModelLabel(): string
    {
        return 'Course purchase';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Course purchases';
    }

    /** Purchases come from Stripe; here an admin can grant, correct or remove access. */
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
                                Section::make('Purchase')
                                    ->icon('lucide-receipt')
                                    ->schema([
                                        Select::make('user_id')
                                            ->label('Customer')
                                            ->relationship('user', 'email')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->prefixIcon('lucide-user-round'),

                                        Select::make('course_id')
                                            ->label('Course')
                                            ->relationship('course', 'name')
                                            ->getOptionLabelFromRecordUsing(fn ($record): string => $record->getTranslation('name', 'fr', false) ?: '—')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->prefixIcon('lucide-graduation-cap'),
                                    ]),
                            ])
                            ->columnSpan([
                                'default' => 1,
                                'xl' => 2,
                                '2xl' => 2,
                            ]),

                        Grid::make(1)
                            ->schema([
                                Section::make('Access')
                                    ->icon('lucide-key-round')
                                    ->schema([
                                        Select::make('status')
                                            ->label('Status')
                                            ->options([
                                                'paid' => 'Paid (access granted)',
                                                'pending' => 'Pending',
                                                'failed' => 'Failed',
                                                'expired' => 'Expired',
                                                'refunded' => 'Refunded (no access)',
                                            ])
                                            ->default('paid')
                                            ->required()
                                            ->native(false)
                                            ->prefixIcon('lucide-badge-check'),
                                    ]),

                                Section::make('Payment')
                                    ->icon('lucide-credit-card')
                                    ->visibleOn('edit')
                                    ->schema([
                                        Placeholder::make('amount')
                                            ->label('Amount')
                                            ->content(fn (?CoursePurchase $record): string => $record
                                                ? number_format($record->amount_cents / 100, 2, ',', ' ').' €'
                                                : '—'),

                                        Placeholder::make('paid_at_info')
                                            ->label('Paid at')
                                            ->content(fn (?CoursePurchase $record): string => $record?->paid_at?->format('d M Y, H:i') ?? '—'),

                                        Placeholder::make('email_info')
                                            ->label('E-mail')
                                            ->content(fn (?CoursePurchase $record): string => $record?->email ?? '—'),

                                        Placeholder::make('stripe_info')
                                            ->label('Stripe payment intent')
                                            ->content(fn (?CoursePurchase $record): string => $record?->granted_manually
                                                ? 'Granted manually'
                                                : ($record?->stripe_payment_intent_id ?? '—')),
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
                TextColumn::make('paid_at')
                    ->label('Paid')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('course_name')
                    ->label('Course')
                    ->state(fn (CoursePurchase $record): string => $record->course?->getTranslation('name', 'fr', false) ?: '—')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                        'course',
                        fn (Builder $course): Builder => $course->whereRaw("name->>'fr' ILIKE ?", ["%{$search}%"]),
                    ))
                    ->weight('medium'),

                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('user.name')
                    ->label('Account')
                    ->placeholder('— (not attached)'),

                TextColumn::make('amount_cents')
                    ->label('Amount')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, ',', ' ').' €'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        default => 'danger',
                    }),

                IconColumn::make('granted_manually')
                    ->label('Manual')
                    ->boolean()
                    ->trueIcon('lucide-hand')
                    ->falseIcon('lucide-credit-card')
                    ->trueColor('info')
                    ->falseColor('gray'),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'paid' => 'Paid',
                    'pending' => 'Pending',
                    'failed' => 'Failed',
                    'expired' => 'Expired',
                    'refunded' => 'Refunded',
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->icon('lucide-trash-2'),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoursePurchases::route('/'),
            'create' => Pages\CreateCoursePurchase::route('/create'),
            'edit' => Pages\EditCoursePurchase::route('/{record}/edit'),
        ];
    }
}
