<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use JsonException;

class ShopController extends Controller
{
    public function index(): View
    {
        $categories = $this->categories();
        $products = $this->products()->paginate(24);

        $metaTitle = __('Boutique professionnelle | BLACK MILK');
        $metaDescription = __('Découvrez les produits professionnels BLACK MILK pour la manucure : gels, bases, tops, soins, outils, accessoires et coffrets.');
        $canonical = $products->currentPage() === 1
            ? route('shop.index')
            : $products->url($products->currentPage());

        return view('shop.index', [
            'categories' => $categories,
            'products' => $products,
            'currentCategory' => null,
            'metaTitle' => $metaTitle,
            'metaDescription' => $metaDescription,
            'canonical' => $canonical,
        ]);
    }

    public function category(string $slug): View|RedirectResponse
    {
        $locale = $this->locale();

        $category = Category::query()
            ->whereRaw("slug->>? = ?", [$locale, $slug])
            ->first();

        if (! $category) {
            $category = Category::query()
                ->whereRaw("slug->>'fr' = ?", [$slug])
                ->firstOrFail();

            $localizedSlug = $category->getTranslation('slug', $locale, false)
                ?: $category->getTranslation('slug', 'fr', false);

            if ($localizedSlug && $localizedSlug !== $slug) {
                return redirect()->route('shop.category', ['slug' => $localizedSlug], 301);
            }
        }

        $categories = $this->categories();

        $products = $this->products()
            ->where('category_id', $category->id)
            ->paginate(24);

        $name = $category->getTranslation('name', $locale, false)
            ?: $category->getTranslation('name', 'fr', false);

        $description = $category->getTranslation('description', $locale, false)
            ?: $category->getTranslation('description', 'fr', false);

        $metaTitle = $category->getTranslation('meta_title', $locale, false)
            ?: "{$name} | BLACK MILK";

        $metaDescription = $category->getTranslation('meta_description', $locale, false)
            ?: Str::limit(
                trim(preg_replace('/\s+/', ' ', strip_tags((string) $description))),
                160,
                ''
            );

        if ($metaDescription === '') {
            $metaDescription = __('Découvrez la collection :category de BLACK MILK.', [
                'category' => $name,
            ]);
        }

        $localizedSlug = $category->getTranslation('slug', $locale, false)
            ?: $category->getTranslation('slug', 'fr', false);

        $canonical = $products->currentPage() === 1
            ? route('shop.category', ['slug' => $localizedSlug])
            : $products->url($products->currentPage());

        return view('shop.index', [
            'categories' => $categories,
            'products' => $products,
            'currentCategory' => $category,
            'metaTitle' => $metaTitle,
            'metaDescription' => $metaDescription,
            'canonical' => $canonical,
        ]);
    }

    public function product(string $slug): View|RedirectResponse
    {
        $locale = $this->locale();

        $product = Product::query()
            ->where('active', true)
            ->with(['category', 'media'])
            ->whereRaw("slug->>? = ?", [$locale, $slug])
            ->first();

        if (! $product) {
            $product = Product::query()
                ->where('active', true)
                ->with(['category', 'media'])
                ->whereRaw("slug->>'fr' = ?", [$slug])
                ->firstOrFail();

            $localizedSlug = $product->getTranslation('slug', $locale, false)
                ?: $product->getTranslation('slug', 'fr', false);

            if ($localizedSlug && $localizedSlug !== $slug) {
                return redirect()->route('shop.product', ['slug' => $localizedSlug], 301);
            }
        }

        $name = $product->getTranslation('name', $locale, false)
            ?: $product->getTranslation('name', 'fr', false);

        $description = $product->getTranslation('description', $locale, false)
            ?: $product->getTranslation('description', 'fr', false);

        $categoryName = $product->category?->getTranslation('name', $locale, false)
            ?: $product->category?->getTranslation('name', 'fr', false);

        $categoryDescription = $product->category?->getTranslation('description', $locale, false)
            ?: $product->category?->getTranslation('description', 'fr', false);

        $metaTitle = $product->getTranslation('meta_title', $locale, false)
            ?: "{$name} – {$categoryName} | BLACK MILK";

        $metaDescription = $product->getTranslation('meta_description', $locale, false)
            ?: $description;

        if (blank($metaDescription) && $categoryDescription) {
            $metaDescription = __(':product fait partie de la collection :category. :description', [
                'product' => $name,
                'category' => $categoryName,
                'description' => $categoryDescription,
            ]);
        }

        if (blank($metaDescription)) {
            $metaDescription = __('Découvrez :product de BLACK MILK, produit professionnel pour la manucure.', [
                'product' => $name,
            ]);
        }

        $metaDescription = Str::limit(
            trim(preg_replace('/\s+/', ' ', strip_tags((string) $metaDescription))),
            160,
            ''
        );

        $localizedSlug = $product->getTranslation('slug', $locale, false)
            ?: $product->getTranslation('slug', 'fr', false);

        $canonical = route('shop.product', ['slug' => $localizedSlug]);

        $recommended = Product::query()
            ->where('active', true)
            ->whereKeyNot($product->id)
            ->with(['category', 'media'])
            ->orderByRaw('CASE WHEN category_id = ? THEN 0 ELSE 1 END', [$product->category_id])
            ->orderByDesc('featured')
            ->orderByDesc('is_new')
            ->orderBy('id')
            ->limit(4)
            ->get();

        return view('shop.product', [
            'product' => $product,
            'recommended' => $recommended,
            'metaTitle' => $metaTitle,
            'metaDescription' => $metaDescription,
            'canonical' => $canonical,
            'ogImage' => $product->getFirstMediaUrl('images'),
        ]);
    }

    public function checkout(): View
    {
        return view('shop.checkout', [
            'deliveries' => Delivery::query()
                ->where('active', true)
                ->orderBy('sort')
                ->get(),
            'metaTitle' => __('Commande | BLACK MILK'),
            'metaDescription' => __('Finalisez votre commande BLACK MILK.'),
            'canonical' => route('checkout'),
        ]);
    }

    public function processOrder(Request $request): JsonResponse
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

        $delivery = Delivery::query()
            ->where('code', $validated['country'])
            ->where('active', true)
            ->first();

        if (! $delivery) {
            throw ValidationException::withMessages([
                'country' => __('La livraison vers cette destination n’est pas disponible.'),
            ]);
        }

        $deliveryCost = $delivery->free_from !== null
            && $subtotal >= (float) $delivery->free_from
                ? 0.0
                : (float) $delivery->price;

        $totalAmount = round($subtotal + $deliveryCost, 2);

        $order = Order::query()->create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'] ?? null,
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'country' => $delivery->name,
            'delivery_code' => $delivery->code,
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

    public function deliveryCost(string $code): JsonResponse
    {
        $delivery = Delivery::query()
            ->where('code', $code)
            ->where('active', true)
            ->first();

        if (! $delivery) {
            return response()->json([
                'success' => false,
                'message' => __('Livraison indisponible.'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'code' => $delivery->code,
            'name' => $delivery->name,
            'carrier' => $delivery->carrier,
            'delay' => $delivery->delay,
            'price' => (float) $delivery->price,
            'free_from' => $delivery->free_from !== null
                ? (float) $delivery->free_from
                : null,
        ]);
    }

    public function orderStatus(string $orderNumber): View
    {
        $order = Order::query()
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        return view('shop.order-status', [
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

            $locale = $this->locale();
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

    private function categories(): Collection
    {
        return Category::query()
            ->whereNotNull('slug')
            ->whereHas('products', fn ($query) => $query->where('active', true))
            ->withCount([
                'products as active_products_count' => fn ($query) => $query->where('active', true),
            ])
            ->orderBy('id')
            ->get();
    }

    private function products()
    {
        return Product::query()
            ->where('active', true)
            ->with('media')
            ->orderByDesc('featured')
            ->orderByDesc('is_new')
            ->orderBy('id');
    }

    private function locale(): string
    {
        $locale = app()->getLocale();

        return in_array($locale, ['fr', 'en', 'ro'], true) ? $locale : 'fr';
    }
}
