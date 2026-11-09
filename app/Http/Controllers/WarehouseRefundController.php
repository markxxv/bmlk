<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderRefundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WarehouseRefundController extends Controller
{
    public function store(Request $request, Order $order, OrderRefundService $service): RedirectResponse
    {
        $data = $request->validate([
            'request_key' => ['required', 'uuid'],
            'type' => ['required', Rule::in(['order', 'item'])],
            'item_index' => ['nullable', 'integer', 'min:0'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'confirmation' => ['required', Rule::in(['CONFIRM REFUND'])],
        ]);

        $refund = $service->create(
            $order,
            $request->user(),
            $data['request_key'],
            $data['type'],
            isset($data['item_index']) ? (int) $data['item_index'] : null,
            isset($data['quantity']) ? (int) $data['quantity'] : null,
        );

        return match ($refund->status) {
            'succeeded' => back()->with('success', 'Stripe accepted the refund. The money will return to the original payment method.'),
            'pending', 'requires_action' => back()->with('success', 'Refund submitted. Stripe is processing the payment.'),
            'failed', 'canceled' => back()->withErrors(['refund' => 'Stripe could not issue this refund. No money was returned.']),
            default => back()->withErrors(['refund' => 'Refund outcome needs verification in Stripe. Do not submit another refund for this amount.']),
        };
    }
}
