<?php

namespace App\Filament\Resources\RoleApplications;

use App\Filament\Resources\RoleApplications\Pages;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Models\RoleApplication;
use App\Models\User;
use Closure;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;

class RoleApplicationResource extends Resource
{
    protected static ?string $model = RoleApplication::class;
    protected static string|BackedEnum|null $navigationIcon = 'lucide-clipboard-check';
    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): ?string
    {
        $count = RoleApplication::query()->where('status', 'pending')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationGroup(): ?string { return 'Shop'; }
    public static function getModelLabel(): string { return 'Partner application'; }
    public static function getPluralModelLabel(): string { return 'Partner applications'; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Application')->icon('lucide-clipboard-check')->columns(2)->schema([
                Placeholder::make('customer_name')->label('Customer')
                    ->content(fn (?RoleApplication $record): string => $record?->user?->name ?? '—'),
                Placeholder::make('customer_email')->label('Email')
                    ->content(fn (?RoleApplication $record): string => $record?->user?->email ?? '—'),
                Select::make('role')->label('Program')->options([
                    'wholesale' => 'Wholesale', 'influencer' => 'Influencer',
                ])->disabled()->dehydrated(false)->prefixIcon('lucide-badge-check'),
                Select::make('status')->label('Decision')->options([
                    'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected',
                ])->required()->native(false)->prefixIcon('lucide-check-circle'),
            ]),
            Section::make('Customer message')->icon('lucide-message-square-text')->schema([
                Placeholder::make('message_preview')->hiddenLabel()
                    ->content(fn (?RoleApplication $record): HtmlString => new HtmlString(
                        '<div class="whitespace-pre-wrap break-words">' . e($record?->message ?? '—') . '</div>'
                    )),
            ]),
            Section::make('Partner statuses')->icon('lucide-badge-check')->columns(2)
                ->visible(fn (?RoleApplication $record): bool => $record?->role === 'influencer')
                ->schema([
                    TextInput::make('influencer_code')->label('Permanent promo code')
                        ->prefixIcon('lucide-ticket-percent')
                        ->nullable()->minLength(4)->maxLength(24)
                        ->rule('regex:/^[A-Za-z0-9_-]{4,24}$/')
                        ->unique(table: User::class, ignorable: fn (?RoleApplication $record): ?User => $record?->user)
                        ->rules([
                            fn (?RoleApplication $record): Closure =>
                                function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                                    if (blank($value)) {
                                        return;
                                    }

                                    $exists = User::query()
                                        ->whereRaw('UPPER(influencer_code) = ?', [Str::upper(trim((string) $value))])
                                        ->where('id', '!=', $record?->user_id)
                                        ->exists();

                                    if ($exists) {
                                        $fail('This promo code is already in use.');
                                    }
                                },
                        ])
                        ->dehydrateStateUsing(fn (?string $state): ?string =>
                            filled($state) ? Str::upper(trim($state)) : null),
                    TextInput::make('influencer_discount')->label('Discount, %')
                        ->prefixIcon('lucide-percent')
                        ->numeric()->step(0.01)->minValue(0)->maxValue(95)
                        ->required()->default(30),
                ]),
            Section::make('Sales by promo code')->icon('lucide-receipt')
                ->visible(fn (?RoleApplication $record): bool => $record?->role === 'influencer'
                    && filled($record?->user?->influencer_code))
                ->schema([
                    Placeholder::make('promo_sales_history')->hiddenLabel()
                        ->content(fn (?RoleApplication $record): HtmlString => self::salesHistory($record)),
                ]),
        ]);
    }

    protected static function salesHistory(?RoleApplication $record): HtmlString
    {
        $code = $record?->user?->influencer_code;
        if (blank($code)) {
            return new HtmlString('—');
        }

        $base = Order::query()
            ->whereRaw('UPPER(promo_code) = ?', [Str::upper($code)])
            ->where('payment_status', 'paid')
            ->where('status', '!=', 'cancelled');

        $totals = (clone $base)->selectRaw('COUNT(*) AS c, COALESCE(SUM(total_amount), 0) AS s, COALESCE(SUM(discount_amount), 0) AS d')->first();
        $orders = (clone $base)->latest('paid_at')->limit(200)->get();

        $icon = fn (string $name, int $size = 16): string => svg('lucide-'.$name, '', [
            'style' => "width:{$size}px;height:{$size}px;flex-shrink:0",
        ])->toHtml();
        $money = fn (float $v): string => number_format($v, 2, '.', ' ').' €';

        $muted = 'color:var(--gray-500)';
        $soft = 'background:color-mix(in srgb,var(--gray-500) 8%,transparent)';
        $line = 'border:1px solid color-mix(in srgb,var(--gray-500) 18%,transparent)';

        $tile = fn (string $ic, string $label, string $value): string => sprintf(
            '<div style="%s;border-radius:14px;padding:14px 16px;display:flex;align-items:center;gap:12px">'
            .'<span style="%s;color:var(--primary-600);width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center">%s</span>'
            .'<div><div style="font-size:12px;%s">%s</div><div style="font-size:18px;font-weight:600;line-height:1.3">%s</div></div></div>',
            $line, 'background:color-mix(in srgb,var(--primary-600) 10%,transparent)', $icon($ic, 18), $muted, e($label), e($value),
        );

        $html = '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px">'
            .$tile('shopping-bag', 'Orders', (string) (int) $totals->c)
            .$tile('euro', 'Revenue', $money((float) $totals->s))
            .$tile('ticket-percent', 'Discounts given', $money((float) $totals->d))
            .'</div>';

        if ($orders->isEmpty()) {
            return new HtmlString($html.'<div style="margin-top:16px;font-size:14px;'.$muted.'">No sales yet.</div>');
        }

        $rows = $orders->map(fn (Order $o): string => sprintf(
            '<a href="%s" style="display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:12px;text-decoration:none;color:inherit" '
            .'onmouseover="this.style.background=\'color-mix(in srgb,var(--gray-500) 8%%,transparent)\'" onmouseout="this.style.background=\'transparent\'">'
            .'<span style="%s;%s;width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center">%s</span>'
            .'<div style="flex:1;min-width:0"><div style="font-size:14px;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">%s</div>'
            .'<div style="display:flex;align-items:center;gap:6px;font-size:12px;%s">%s<span>%s</span></div></div>'
            .'<span style="font-size:12px;color:var(--success-600);%s;padding:3px 9px;border-radius:999px;white-space:nowrap">− %s</span>'
            .'<span style="font-size:14px;font-weight:600;min-width:84px;text-align:right">%s</span>%s</a>',
            e(OrderResource::getUrl('edit', ['record' => $o])),
            $soft, $muted, $icon('receipt', 16),
            e($o->order_number ?? '#'.$o->id),
            $muted, $icon('calendar', 13), e(($o->paid_at ?? $o->created_at)?->format('d.m.Y')),
            'background:color-mix(in srgb,var(--success-600) 10%,transparent)',
            e($money((float) $o->discount_amount)),
            e($money((float) $o->total_amount)),
            '<span style="'.$muted.'">'.$icon('chevron-right', 16).'</span>',
        ))->implode('');

        return new HtmlString($html.'<div style="margin-top:14px;max-height:380px;overflow:auto;display:flex;flex-direction:column;gap:2px">'.$rows.'</div>');
    }

    /**
     * Paid, non-cancelled orders placed with the partner's promo code (includes
     * orders imported from the old site) => [count, revenue incl. VAT].
     *
     * @return array{0: ?int, 1: ?float}
     */
    protected static function promoSales(RoleApplication $record): array
    {
        static $cache = [];

        $code = $record->user?->influencer_code;
        if ($record->role !== 'influencer' || blank($code)) {
            return [null, null];
        }

        return $cache[Str::upper($code)] ??= (function () use ($code): array {
            $row = Order::query()
                ->whereRaw('UPPER(promo_code) = ?', [Str::upper($code)])
                ->where('payment_status', 'paid')
                ->where('status', '!=', 'cancelled')
                ->selectRaw('COUNT(*) AS c, COALESCE(SUM(total_amount), 0) AS s')
                ->first();

            return [(int) $row->c, (float) $row->s];
        })();
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('user.name')->label('Customer')->searchable(),
            TextColumn::make('user.email')->label('Email')->searchable(),
            TextColumn::make('role')->badge()->color('gray'),
            TextColumn::make('user.influencer_code')->label('Promo code')->badge()->color('gray')
                ->searchable()->placeholder('—')->copyable(),
            TextColumn::make('user.influencer_discount')->label('Discount')
                ->suffix(' %')->numeric(decimalPlaces: 0)
                ->placeholder('—')
                ->visible(true)
                ->state(fn (RoleApplication $record): ?float => $record->role === 'influencer'
                    ? (float) $record->user?->influencer_discount
                    : null),
            TextColumn::make('promo_orders')->label('Sales')
                ->state(fn (RoleApplication $record): ?int => self::promoSales($record)[0])
                ->placeholder('—'),
            TextColumn::make('promo_revenue')->label('Revenue')
                ->state(fn (RoleApplication $record): ?float => self::promoSales($record)[1])
                ->money('EUR')->placeholder('—'),
            TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                'approved' => 'success', 'rejected' => 'danger', default => 'warning',
            }),
            TextColumn::make('created_at')->dateTime('d M Y H:i')->sortable(),
        ])->filters([
            SelectFilter::make('role')->options(['wholesale' => 'Wholesale', 'influencer' => 'Influencer']),
            SelectFilter::make('status')->options(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected']),
        ])->recordActions([EditAction::make()->icon('lucide-pencil')])
          ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoleApplications::route('/'),
            'edit' => Pages\EditRoleApplication::route('/{record}/edit'),
        ];
    }
}
