<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ReviewAccessToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductRatingWriter
{
    /**
     * Either an authenticated order owner or a valid email-link holder may
     * save ratings. Both paths enforce identical validation and order locks.
     */
    public function save(Order $order, array $ratings, ?int $userId = null, ?string $tokenHash = null): void
    {
        DB::transaction(function () use ($order, $ratings, $userId, $tokenHash): void {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($userId !== null) {
                abort_unless($locked->user_id === $userId, 404);
            } elseif ($tokenHash !== null) {
                abort_unless(
                    ReviewAccessToken::query()
                        ->where('order_id', $locked->id)
                        ->where('token_hash', $tokenHash)
                        ->where('expires_at', '>', now())
                        ->exists(),
                    404,
                );
            } else {
                abort(403);
            }

            abort_unless($locked->canBeRated(), 403);

            $items = $locked->rateableItems();
            $saved = 0;

            foreach ($ratings as $index => $rating) {
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

                $locked->ratings()->updateOrCreate(
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
    }
}
