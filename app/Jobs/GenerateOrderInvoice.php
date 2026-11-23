<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\Invoices\OrderInvoiceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateOrderInvoice implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $orderId)
    {
        $this->onConnection('database');
        $this->onQueue('invoices');
    }

    public function handle(OrderInvoiceService $invoices): void
    {
        $order = Order::query()->find($this->orderId);

        if ($order) {
            $invoices->issue($order);
        }
    }
}
