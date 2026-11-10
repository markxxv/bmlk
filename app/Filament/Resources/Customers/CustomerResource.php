<?php

namespace App\Filament\Resources\Customers;

use App\Filament\Resources\Customers\Pages;
use App\Http\Controllers\CustomerPreviewController;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = User::class;
    protected static string|BackedEnum|null $navigationIcon = 'lucide-users-round';
    protected static ?int $navigationSort = 4;
    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string { return 'Shop'; }
    public static function getModelLabel(): string { return 'Customer'; }
    public static function getPluralModelLabel(): string { return 'Customers'; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Customer')->icon('lucide-user-round')->columns(2)->schema([
                TextInput::make('name')->prefixIcon('lucide-user-round')->required()->maxLength(255),
                TextInput::make('email')->prefixIcon('lucide-mail')->disabled()->copyable(),
            ]),
            Section::make('Partner statuses')->icon('lucide-badge-check')->columns(2)->schema([
                Toggle::make('is_wholesale')->label('Wholesale')->onIcon('lucide-check')->offIcon('lucide-x'),
                Toggle::make('is_influencer')->label('Influencer')->onIcon('lucide-check')->offIcon('lucide-x'),
                TextInput::make('influencer_code')->label('Permanent promo code')
                    ->prefixIcon('lucide-ticket-percent')->disabled(),
                TextInput::make('influencer_discount')->label('Discount, %')
                    ->prefixIcon('lucide-percent')->numeric()->step(0.01)
                    ->minValue(0)->maxValue(95)->required()->default(30),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Customer')->searchable()->weight('medium'),
            TextColumn::make('email')->icon('lucide-mail')->searchable()->copyable(),
            IconColumn::make('email_verified_at')->label('Verified')
                ->boolean()->state(fn (User $record): bool => $record->hasVerifiedEmail()),
            IconColumn::make('is_wholesale')->label('Wholesale')->boolean(),
            IconColumn::make('is_influencer')->label('Influencer')->boolean(),
            TextColumn::make('influencer_code')->label('Promo')->badge()->color('gray')->placeholder('—'),
            TextColumn::make('created_at')->label('Joined')->date('d M Y')->sortable(),
        ])->filters([
            TernaryFilter::make('is_wholesale'),
            TernaryFilter::make('is_influencer'),
        ])->recordActions([
            Action::make('loginAsCustomer')
                ->label('Login as user')
                ->icon('lucide-eye')
                ->color('gray')
                ->visible(fn (User $record): bool => ! $record->is_admin && ! $record->is_warehouse)
                ->requiresConfirmation()
                ->modalHeading('Preview customer account')
                ->modalDescription('Open this customer’s real account in read-only mode. Orders and profile are visible; changes, checkout and payments are blocked. You can return to admin at any time.')
                ->modalSubmitActionLabel('Start preview')
                ->action(fn (User $record) => app(CustomerPreviewController::class)->start(request(), $record)),
            EditAction::make()->icon('lucide-pencil'),
        ])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\OrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
