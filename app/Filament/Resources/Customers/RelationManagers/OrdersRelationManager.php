<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $title = 'Orders';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('order_number')
            ->columns([
                TextColumn::make('order_number')->label('Order')->searchable()->weight('medium'),
                TextColumn::make('created_at')->label('Created')->dateTime('d M Y, H:i')->sortable(),
                TextColumn::make('total_amount')->label('Total')->money('EUR')->sortable(),
                TextColumn::make('payment_status')->label('Payment')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'processing' => 'info',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('status')->label('Fulfillment')->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'To prepare',
                        'processing' => 'Preparing',
                        'shipped' => 'Shipped',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'archived' => 'Archived',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processing', 'shipped' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('tracking_number')->label('Tracking')->placeholder('—')->copyable()->toggleable(),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Open')
                    ->icon('lucide-external-link')
                    ->url(fn (Order $record): string => OrderResource::getUrl('edit', ['record' => $record])),
            ])
            ->recordUrl(fn (Order $record): string => OrderResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No orders yet');
    }
}
