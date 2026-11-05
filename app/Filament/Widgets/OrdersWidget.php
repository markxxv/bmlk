<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class OrdersWidget extends Widget
{
    // Directly below the shop stats, before any future dashboard widgets.
    protected static ?int $sort = -9;

    protected string $view = 'filament.widgets.orders-widget';

    protected int | string | array $columnSpan = 'full';
}
