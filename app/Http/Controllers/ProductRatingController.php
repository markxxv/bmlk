<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\ProductRatingWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductRatingController extends Controller
{
    public function store(Request $request, Order $order, ProductRatingWriter $writer): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        $data = $request->validate([
            'ratings' => ['required', 'array', 'min:1'],
            'ratings.*' => ['nullable', 'integer', 'between:1,5'],
        ]);

        $writer->save($order, $data['ratings'], userId: (int) $request->user()->id);

        return back()->with('success', __('Vos évaluations ont été enregistrées.'));
    }
}
