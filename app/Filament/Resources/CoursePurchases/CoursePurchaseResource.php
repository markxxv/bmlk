<?php

namespace App\Filament\Resources\CoursePurchases;

use App\Filament\Resources\CoursePurchases\Pages;
use App\Models\CoursePurchase;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

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
        return $schema->components([
            Select::make('user_id')
                ->label('Customer')
                ->relationship('user', 'email')
                ->searchable()
                ->preload()
                ->required(),

            Select::make('course_id')
                ->label('Course')
                ->relationship('course', 'name')
                ->getOptionLabelFromRecordUsing(fn ($record): string => $record->getTranslation('name', 'fr', false) ?: '—')
                ->searchable()
                ->preload()
                ->required(),

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
                ->native(false),

            TextInput::make('email')->label('E-mail')->email()->disabled()->dehydrated(false)
                ->visibleOn('edit'),

            TextInput::make('stripe_payment_intent_id')->label('Stripe payment intent')->disabled()->dehydrated(false)
                ->visibleOn('edit'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('paid_at')->label('Paid')->dateTime('d M Y, H:i')->placeholder('—')->sortable(),
                TextColumn::make('course_name')
                    ->label('Course')
                    ->state(fn (CoursePurchase $record): string => $record->course?->getTranslation('name', 'fr', false) ?: '—')
                    ->searchable(query: fn ($query, string $search) => $query->whereHas('course', fn ($q) => $q->whereRaw("name->>'fr' ILIKE ?", ["%{$search}%"]))),
                TextColumn::make('email')->label('E-mail')->searchable()->placeholder('—'),
                TextColumn::make('user.name')->label('Account')->placeholder('— (not attached)'),
                TextColumn::make('amount_cents')->label('Amount')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, ',', ' ').' €'),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success', 'pending' => 'warning', default => 'danger',
                    }),
                IconColumn::make('granted_manually')->label('Manual')->boolean(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'paid' => 'Paid', 'pending' => 'Pending', 'failed' => 'Failed', 'expired' => 'Expired', 'refunded' => 'Refunded',
                ]),
            ])
            ->recordActions([
                EditAction::make()->icon('lucide-pencil'),
                DeleteAction::make()->icon('lucide-trash-2'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->icon('lucide-trash-2'),
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
