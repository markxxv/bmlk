<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductRatingController extends Controller
{
    public function store(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        $data = $request->validate([
            'ratings' => ['required', 'array', 'min:1'],
            'ratings.*' => ['nullable', 'integer', 'between:1,5'],
        ]);

        DB::transaction(function () use ($request, $order, $data): void {
            // Serialize edits and recheck ownership/payment/delivery.
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            abort_unless($order->user_id === $request->user()->id, 404);
            abort_unless($order->canBeRated(), 403);

            $items = $order->rateableItems();
            $saved = 0;

            foreach ($data['ratings'] as $index => $rating) {
                if ($rating === null || $rating === '') {
                    continue;
                }

                if (! ctype_digit((string) $index) || ! array_key_exists((int) $index, $items)) {
                    throw ValidationException::withMessages([
                        'ratings' => __('Ce produit ne fait pas partie de cette commande.'),
                    ]);
                }

                $item = $items[(int) $index];
                $productId = (int) $item['product_id'];
                $exists = Product::withTrashed()->whereKey($productId)->exists();

                $order->ratings()->updateOrCreate(
                    ['order_item_index' => (int) $index],
                    [
                        'product_id' => $exists ? $productId : null,
                        'product_name' => mb_substr((string) ($item['name'] ?? __('Produit')), 0, 255),
                        'rating' => (int) $rating,
                    ],
                );

                $saved++;
            }

            if ($saved === 0) {
                throw ValidationException::withMessages([
                    'ratings' => __('Choisissez au moins une note.'),
                ]);
            }
        });

        return back()->with('success', __('Vos évaluations ont été enregistrées.'));
    }
}
