<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use JsonException;

class CheckoutController extends Controller
{
    public function show(): View
    {
        return view('checkout', [
            'metaTitle' => __('Commande | BLACK MILK'),
            'metaDescription' => __('Finalisez votre commande BLACK MILK.'),
            'canonical' => route('checkout'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'country' => ['required', 'string', 'max:120'],
            'zip' => ['nullable', 'string', 'max:30'],
            'city' => ['required', 'string', 'max:120'],
            'address' => ['required', 'string', 'max:1000'],
            'message' => ['nullable', 'string', 'max:2000'],
            'privacy' => ['accepted'],
            'cart_items' => ['required', 'string'],
        ]);

        try {
            $cartItems = json_decode(
                $validated['cart_items'],
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (JsonException) {
            throw ValidationException::withMessages([
                'cart_items' => __('Le panier est invalide.'),
            ]);
        }

        if (! is_array($cartItems) || $cartItems === []) {
            throw ValidationException::withMessages([
                'cart_items' => __('Votre panier est vide.'),
            ]);
        }

        $items = $this->normalizeItems($cartItems);

        $subtotal = round(
            $items->sum(fn (array $item): float => (float) $item['line_total']),
            2,
        );

        $deliveryCost = 0.0;
        $totalAmount = round($subtotal + $deliveryCost, 2);

        $order = Order::query()->create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'] ?? null,
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'country' => $validated['country'],
            'zip' => $validated['zip'] ?? null,
            'city' => $validated['city'],
            'address' => $validated['address'],
            'message' => $validated['message'] ?? null,
            'items' => $items->values()->all(),
            'subtotal' => $subtotal,
            'delivery_cost' => $deliveryCost,
            'total_amount' => $totalAmount,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'redirect' => route('order.status', ['orderNumber' => $order->order_number]),
        ]);
    }

    public function status(string $orderNumber): View
    {
        $order = Order::query()
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        return view('order-status', [
            'order' => $order,
            'metaTitle' => __('Commande :number | BLACK MILK', [
                'number' => $order->order_number,
            ]),
        ]);
    }

    private function normalizeItems(array $cartItems): Collection
    {
        $items = collect($cartItems);

        $productIds = $items
            ->pluck('product_id')
            ->filter()
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        $products = Product::query()
            ->where('active', true)
            ->whereIn('id', $productIds)
            ->with('media')
            ->get()
            ->keyBy('id');

        return $items->map(function (mixed $cartItem) use ($products): array {
            if (! is_array($cartItem)) {
                throw ValidationException::withMessages([
                    'cart_items' => __('Le panier contient une ligne invalide.'),
                ]);
            }

            $productId = (int) ($cartItem['product_id'] ?? 0);
            $product = $products->get($productId);

            if (! $product) {
                throw ValidationException::withMessages([
                    'cart_items' => __('Un produit du panier n’est plus disponible.'),
                ]);
            }

            $quantity = (int) ($cartItem['quantity'] ?? 1);

            if ($quantity < 1 || $quantity > 99) {
                throw ValidationException::withMessages([
                    'cart_items' => __('La quantité du produit est invalide.'),
                ]);
            }

            $selectedOptions = collect($cartItem['options'] ?? [])
                ->filter(fn (mixed $option): bool => is_array($option))
                ->keyBy(fn (array $option): string => (string) ($option['id'] ?? ''));

            $normalizedOptions = [];
            $unitPrice = $product->price !== null
                ? (float) $product->price
                : null;

            foreach ($product->options ?? [] as $option) {
                if (! is_array($option)) {
                    continue;
                }

                $optionId = (string) data_get($option, 'id');

                if ($optionId === '') {
                    continue;
                }

                $selected = $selectedOptions->get($optionId);

                if (! is_array($selected)) {
                    throw ValidationException::withMessages([
                        'cart_items' => __('Sélectionnez toutes les options du produit.'),
                    ]);
                }

                $values = collect(data_get($option, 'values', []));
                $label = data_get($option, 'label.'.strtoupper(app()->getLocale()))
                    ?: data_get($option, 'label.FR')
                    ?: $optionId;

                $objectValues = $values->filter(fn (mixed $value): bool => is_array($value));

                if ($objectValues->isNotEmpty()) {
                    $selectedQuantity = data_get($selected, 'quantity');

                    $matched = $objectValues->first(
                        fn (array $value): bool =>
                            (int) data_get($value, 'quantity') === (int) $selectedQuantity
                    );

                    if (! is_array($matched)) {
                        throw ValidationException::withMessages([
                            'cart_items' => __('Une option du produit est invalide.'),
                        ]);
                    }

                    $optionPrice = data_get($matched, 'price.amount');

                    if ($optionPrice !== null) {
                        $unitPrice = (float) $optionPrice;
                    }

                    $normalizedOptions[] = [
                        'id' => $optionId,
                        'label' => $label,
                        'value' => null,
                        'quantity' => (int) data_get($matched, 'quantity'),
                        'display' => data_get($matched, 'quantity').' ×',
                    ];

                    continue;
                }

                $selectedValue = (string) data_get($selected, 'value', '');

                $matched = $values->first(
                    fn (mixed $value): bool =>
                        ! is_array($value) && (string) $value === $selectedValue
                );

                if ($matched === null) {
                    throw ValidationException::withMessages([
                        'cart_items' => __('Une option du produit est invalide.'),
                    ]);
                }

                $normalizedOptions[] = [
                    'id' => $optionId,
                    'label' => $label,
                    'value' => (string) $matched,
                    'quantity' => null,
                    'display' => (string) $matched,
                ];
            }

            if ($unitPrice === null) {
                throw ValidationException::withMessages([
                    'cart_items' => __('Le prix d’un produit est indisponible.'),
                ]);
            }

            $locale = app()->getLocale();
            $name = $product->getTranslation('name', $locale, false)
                ?: $product->getTranslation('name', 'fr', false);

            $slug = $product->getTranslation('slug', $locale, false)
                ?: $product->getTranslation('slug', 'fr', false);

            $unitPrice = round($unitPrice, 2);

            return [
                'product_id' => $product->id,
                'name' => $name,
                'url' => $slug
                    ? route('shop.product', ['slug' => $slug])
                    : route('shop.index'),
                'image' => $product->getFirstMediaUrl('images', 'thumb')
                    ?: $product->getFirstMediaUrl('images'),
                'options' => $normalizedOptions,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => round($unitPrice * $quantity, 2),
            ];
        });
    }
}
