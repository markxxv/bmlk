<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages;
use App\Models\Order;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static string|BackedEnum|null $navigationIcon = 'lucide-receipt-text';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'order_number';

    public static function getNavigationGroup(): ?string
    {
        return 'Shop';
    }

    public static function getModelLabel(): string
    {
        return 'Order';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Orders';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Order')->columns(2)->schema([
                TextInput::make('order_number')->disabled(),
                Select::make('status')->label('Order status')->options([
                    'pending' => 'Pending', 'processing' => 'Preparing',
                    'shipped' => 'Shipped', 'completed' => 'Completed', 'cancelled' => 'Cancelled',
                ])->required(),
                Select::make('payment_status')->label('Payment')->options([
                    'unpaid' => 'Unpaid', 'processing' => 'Processing',
                    'failed' => 'Failed', 'paid' => 'Paid',
                ])->disabled()->dehydrated(false),
                TextInput::make('total_amount')->label('Total (€)')->disabled(),
                TextInput::make('stripe_payment_intent_id')->label('Stripe payment ID')->disabled()->columnSpanFull(),
            ]),
            Section::make('Customer and shipping')->columns(2)->schema([
                TextInput::make('first_name')->disabled(),
                TextInput::make('last_name')->disabled(),
                TextInput::make('email')->disabled(),
                TextInput::make('phone')->disabled(),
                TextInput::make('country')->disabled(),
                TextInput::make('city')->disabled(),
                TextInput::make('zip')->disabled(),
                Textarea::make('address')->disabled()->columnSpanFull(),
                Textarea::make('message')->disabled()->columnSpanFull(),
            ]),
            Section::make('Items')->schema([
                Textarea::make('items')->label('Purchased items')
                    ->formatStateUsing(fn (?array $state): string => collect($state ?? [])
                        ->map(fn (array $item): string => ($item['name'] ?? 'Item')
                            .' × '.($item['quantity'] ?? 1)
                            .' — '.number_format((float) ($item['line_total'] ?? 0), 2).' €'
                            .collect($item['options'] ?? [])->pluck('display')->filter()->map(fn ($v) => ' / '.$v)->implode(''))
                        ->implode("\n"))
                    ->rows(8)->disabled()->dehydrated(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('order_number')->label('Order')->searchable()->weight('medium'),
            TextColumn::make('created_at')->label('Created')->dateTime('d M Y, H:i')->sortable(),
            TextColumn::make('first_name')->label('Customer')
                ->formatStateUsing(fn (Order $record): string => trim($record->first_name.' '.$record->last_name)),
            TextColumn::make('email')->searchable()->toggleable(),
            TextColumn::make('total_amount')->label('Total')->money('EUR')->sortable(),
            TextColumn::make('payment_status')->label('Payment')->badge()
                ->color(fn (string $state): string => match ($state) {
                    'paid' => 'success', 'processing' => 'info', 'failed' => 'danger', default => 'gray',
                }),
            TextColumn::make('status')->label('Order status')->badge()->color('gray'),
        ])
        ->filters([
            SelectFilter::make('payment_status')->options([
                'unpaid' => 'Unpaid', 'processing' => 'Processing', 'failed' => 'Failed', 'paid' => 'Paid',
            ]),
            SelectFilter::make('status')->options([
                'pending' => 'Pending', 'processing' => 'Preparing',
                'shipped' => 'Shipped', 'completed' => 'Completed', 'cancelled' => 'Cancelled',
            ]),
        ])
        ->recordActions([EditAction::make()])
        ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
