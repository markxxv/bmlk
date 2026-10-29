<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages;
use App\Models\Order;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

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
            Grid::make(['default' => 1, 'xl' => 2])
                ->schema([
                    Grid::make(1)->schema([
                        Section::make('Order')
                            ->icon('lucide-receipt-text')
                            ->columns(2)
                            ->schema([
                                TextInput::make('order_number')
                                    ->label('Order number')
                                    ->prefixIcon('lucide-hash')
                                    ->disabled(),

                                Select::make('status')
                                    ->label('Order status')
                                    ->prefixIcon('lucide-package-check')
                                    ->native(false)
                                    ->options([
                                        'pending' => 'Pending',
                                        'processing' => 'Preparing',
                                        'shipped' => 'Shipped',
                                        'completed' => 'Completed',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->required(),

                                Select::make('payment_status')
                                    ->label('Payment')
                                    ->prefixIcon('lucide-credit-card')
                                    ->native(false)
                                    ->options([
                                        'unpaid' => 'Unpaid',
                                        'processing' => 'Processing',
                                        'failed' => 'Failed',
                                        'paid' => 'Paid',
                                    ])
                                    ->disabled()
                                    ->dehydrated(false),

                                TextInput::make('total_amount')
                                    ->label('Total (€)')
                                    ->prefixIcon('lucide-euro')
                                    ->disabled(),

                                TextInput::make('stripe_payment_intent_id')
                                    ->label('Stripe payment ID')
                                    ->prefixIcon('lucide-fingerprint')
                                    ->disabled()
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Customer and shipping')
                            ->icon('lucide-truck')
                            ->columns(2)
                            ->schema([
                                TextInput::make('first_name')
                                    ->label('First name')
                                    ->prefixIcon('lucide-user-round')
                                    ->disabled(),

                                TextInput::make('last_name')
                                    ->label('Last name')
                                    ->prefixIcon('lucide-user-round')
                                    ->disabled(),

                                TextInput::make('email')
                                    ->prefixIcon('lucide-mail')
                                    ->disabled(),

                                TextInput::make('phone')
                                    ->prefixIcon('lucide-phone')
                                    ->disabled(),

                                TextInput::make('country')
                                    ->prefixIcon('lucide-globe')
                                    ->disabled(),

                                TextInput::make('city')
                                    ->prefixIcon('lucide-map-pin')
                                    ->disabled(),

                                TextInput::make('zip')
                                    ->label('Postal code')
                                    ->prefixIcon('lucide-mailbox')
                                    ->disabled(),

                                TextInput::make('tracking_url')
                                    ->label('Tracking URL')
                                    ->prefixIcon('lucide-link')
                                    ->disabled()
                                    ->columnSpanFull(),

                                TextInput::make('shipping_option_name')
                                    ->label('Sendcloud shipping method')
                                    ->prefixIcon('lucide-truck')
                                    ->disabled()
                                    ->columnSpanFull(),
                                TextInput::make('packaging_summary')
                                    ->label('Shipping package')
                                    ->prefixIcon('lucide-package')
                                    ->formatStateUsing(function (?Order $record): string {
                                        $parcel = $record?->shipping_parcel;
                                        if (! is_array($parcel)) {
                                            return '—';
                                        }
                                        $size = $parcel['dimensions'] ?? [];
                                        $dimensions = implode(' × ', [
                                            $size['length'] ?? '?',
                                            $size['width'] ?? '?',
                                            $size['height'] ?? '?',
                                        ]);
                                        return ($parcel['box_name'] ?? '—').' · '.$dimensions.' cm · '.($parcel['total_weight_g'] ?? '?').' g';
                                    })
                                    ->helperText(fn (?Order $record): ?string =>
                                        data_get($record?->shipping_parcel, 'estimated')
                                            ? 'Dimensions et poids estimés : vérifier le colis réel avant de créer une étiquette.'
                                            : null
                                    )
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpanFull(),

                                TextInput::make('tracking_number')
                                    ->label('Tracking number')
                                    ->prefixIcon('lucide-package-search')
                                    ->maxLength(190)
                                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? trim($state) : null)
                                    ->columnSpanFull(),

                                Textarea::make('address')
                                    ->label('Address')
                                    ->hintIcon('lucide-map-pinned')
                                    ->rows(2)
                                    ->disabled()
                                    ->columnSpanFull(),

                                Textarea::make('message')
                                    ->label('Customer message')
                                    ->hintIcon('lucide-message-square-text')
                                    ->rows(3)
                                    ->disabled()
                                    ->columnSpanFull(),
                            ]),
                    ]),

                    Section::make('Items')
                        ->icon('lucide-shopping-bag')
                        ->schema([
                            Placeholder::make('order_items_preview')
                                ->label('Purchased items')
                                ->content(fn (?Order $record): HtmlString => new HtmlString(static::itemsHtml($record))),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    private static function itemsHtml(?Order $record): string
    {
        if (! $record || empty($record->items)) {
            return '<p class="text-sm text-gray-500 dark:text-gray-400">No items</p>';
        }

        $html = '<div class="space-y-3">';

        foreach ($record->items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = e((string) data_get($item, 'name', 'Product'));
            $quantity = max(1, (int) data_get($item, 'quantity', 1));
            $unitPrice = (float) data_get($item, 'unit_price', 0);
            $lineTotal = (float) data_get($item, 'line_total', $unitPrice * $quantity);
            $image = data_get($item, 'image');
            $options = collect(data_get($item, 'options', []))
                ->filter(fn (mixed $option): bool => is_array($option))
                ->map(function (array $option): string {
                    $label = trim((string) data_get($option, 'label', ''));
                    $value = trim((string) data_get($option, 'display', ''));

                    return $label && $value ? "{$label}: {$value}" : ($value ?: $label);
                })
                ->filter()
                ->map(fn (string $option): string => e($option))
                ->implode(' · ');

            $html .= '<div class="flex items-start gap-4 rounded-xl bg-gray-50 p-4 dark:bg-gray-800">';

            if (is_string($image) && preg_match('~^(https?://|/)~i', $image)) {
                $html .= '<img src="'.e($image).'" alt="'.$name.'" class="h-16 w-16 shrink-0 rounded-lg object-cover">';
            } else {
                $html .= '<div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-400 dark:bg-gray-700">—</div>';
            }

            $html .= '<div class="min-w-0 flex-1">';
            $html .= '<p class="text-sm font-medium text-gray-900 dark:text-gray-100">'.$name.'</p>';

            if ($options !== '') {
                $html .= '<p class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400">'.$options.'</p>';
            }

            $html .= '<p class="mt-2 text-xs text-gray-500 dark:text-gray-400">'
                .$quantity.' × '.number_format($unitPrice, 2, ',', ' ').' €</p>';
            $html .= '</div>';
            $html .= '<div class="shrink-0 text-right text-sm font-semibold text-gray-900 dark:text-gray-100">'
                .number_format($lineTotal, 2, ',', ' ').' €</div>';
            $html .= '</div>';
        }

        $html .= '<div class="mt-5 space-y-3 border-t border-gray-200 pt-5 text-sm dark:border-gray-700">';
        $html .= '<div class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">Subtotal</span>'
            .'<span class="font-medium">'.number_format((float) $record->subtotal, 2, ',', ' ').' €</span></div>';
        if ((float) $record->discount_amount > 0) {
            $html .= '<div class="flex justify-between gap-4 text-bmlk-brand"><span>Discount'
                .($record->promo_code ? ' · '.e($record->promo_code) : '').'</span>'
                .'<span>− '.number_format((float) $record->discount_amount, 2, ',', ' ').' €</span></div>';
        }

        $html .= '<div class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">Delivery'
            .($record->country ? ' ('.e($record->country).')' : '').'</span>'
            .'<span class="font-medium">'.number_format((float) $record->delivery_cost, 2, ',', ' ').' €</span></div>';
        $html .= '<div class="flex justify-between gap-4 border-t border-gray-200 pt-4 text-base font-semibold text-gray-900 dark:border-gray-700 dark:text-gray-100">'
            .'<span>Total</span><span>'.number_format((float) $record->total_amount, 2, ',', ' ').' €</span></div>';
        $html .= '</div></div>';

        return $html;
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
                    'paid' => 'success',
                    'processing' => 'info',
                    'failed' => 'danger',
                    default => 'gray',
                }),
            TextColumn::make('status')->label('Order status')->badge()->color('gray'),
            TextColumn::make('tracking_number')->label('Tracking')
                ->placeholder('—')
                ->copyable()
                ->toggleable(),
        ])
        ->filters([
            SelectFilter::make('payment_status')->options([
                'unpaid' => 'Unpaid',
                'processing' => 'Processing',
                'failed' => 'Failed',
                'paid' => 'Paid',
            ]),
            SelectFilter::make('status')->options([
                'pending' => 'Pending',
                'processing' => 'Preparing',
                'shipped' => 'Shipped',
                'completed' => 'Completed',
                'cancelled' => 'Cancelled',
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
