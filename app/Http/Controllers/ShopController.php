<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
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
            'ogImage' => $product->storefrontImageUrl('detail'),
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
            'email' => ['required', 'email', 'max:255'],
            'country' => ['required', 'string', 'size:2'],
            'zip' => ['required', 'string', 'max:30'],
            'city' => ['required', 'string', 'max:120'],
            'address' => ['required', 'string', 'max:1000'],
            'message' => ['nullable', 'string', 'max:2000'],
            'privacy' => ['accepted'],
            'cart_items' => ['required', 'string'],
            'promo_code' => ['nullable', 'string', 'max:32'],
            'shipping_option' => ['required', 'string', 'max:190'],
            'shipping_contract_id' => ['required', 'integer', 'min:1'],
            'shipping_quoted_price' => ['required', 'numeric', 'min:0'],
            'service_point_id' => ['nullable', 'integer', 'min:1'],
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

        // Recalculate with the actual parcel and Sendcloud tariff; never trust a quoted price from JS.
        $shipping = app(SendcloudController::class);
        $countryCode = strtoupper($validated['country']);
        $quote = $shipping->options(
            $items, $countryCode, $validated['zip'], $validated['city'],
            $validated['address'], $subtotal,
        );
        $delivery = $quote['zone'];
        $selected = collect($quote['options'])->first(fn (array $option): bool =>
            $option['code'] === $validated['shipping_option']
                && $option['contract_id'] === (int) $validated['shipping_contract_id']
        );
        if (! $selected) {
            throw ValidationException::withMessages([
                'shipping_option' => __('Le mode de livraison n’est plus disponible. Actualisez les tarifs.'),
            ]);
        }
        if ((int) round($selected['price'] * 100) !== (int) round((float) $validated['shipping_quoted_price'] * 100)) {
            throw ValidationException::withMessages([
                'shipping_option' => __('Le tarif a changé. Actualisez les tarifs avant de payer.'),
            ]);
        }
        $point = null;
        if ($selected['requires_point']) {
            if (empty($validated['service_point_id'])) {
                throw ValidationException::withMessages([
                    'service_point_id' => __('Choisissez un point relais.'),
                ]);
            }
            $point = $shipping->pointDetails(
                (int) $validated['service_point_id'], $countryCode, $selected['carrier_code'],
            );
        }
        $deliveryCost = $selected['price'];

        $promoCode = Str::upper(trim((string) ($validated['promo_code'] ?? '')));
        $influencer = $promoCode !== ''
            ? User::query()->where('influencer_code', $promoCode)->where('is_influencer', true)
                ->whereNotNull('email_verified_at')->first()
            : null;

        if ($promoCode !== '' && ! $influencer) {
            throw ValidationException::withMessages(['promo_code' => __('Code promotionnel invalide.')]);
        }

        $percent = $influencer ? (float) $influencer->influencer_discount : 0;
        $discountAmount = 0.0;

        if ($influencer) {
            $items = $items->map(function (array $item) use ($percent, &$discountAmount): array {
                $originalCents = (int) round($item['unit_price'] * 100);
                $discountedCents = (int) round($originalCents * (100 - $percent) / 100);
                $item['discounted_unit_price'] = $discountedCents / 100;
                $discountAmount += ($originalCents - $discountedCents) * $item['quantity'] / 100;
                return $item;
            });
        }

        $discountAmount = round($discountAmount, 2);
        $totalAmount = round($subtotal - $discountAmount + $deliveryCost, 2);

        // Private browser-only proof for finishing registration after returning
        // from Stripe. The public order URL token must never authenticate a user.
        $activationToken = Str::random(64);

        $order = Order::query()->create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'] ?? null,
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'country' => SendcloudController::countries()[$countryCode],
            'delivery_code' => $delivery->code,
            'shipping_country_code' => $countryCode,
            'shipping_option_code' => $selected['code'],
            'shipping_contract_id' => $selected['contract_id'],
            'shipping_option_name' => $selected['carrier'].' — '.$selected['name'],
            'sendcloud_rate' => $selected['actual_price'],
            'shipping_parcel' => $quote['parcel'],
            'service_point_id' => $point['id'] ?? null,
            'service_point_name' => $point['name'] ?? null,
            'service_point_address' => $point['address'] ?? null,
            'zip' => $validated['zip'] ?? null,
            'city' => $validated['city'],
            'address' => $validated['address'],
            'message' => $validated['message'] ?? null,
            'items' => $items->values()->all(),
            'subtotal' => $subtotal,
            'delivery_cost' => $deliveryCost,
            'total_amount' => $totalAmount,
            'promo_code' => $influencer?->influencer_code,
            'discount_amount' => $discountAmount,
            'status' => 'pending',
            'user_id' => $request->user() && Str::lower($request->user()->email) === Str::lower($validated['email'])
                ? $request->user()->id : null,
            'payment_status' => 'unpaid',
            'access_token' => Str::random(48),
            'onboarding_token_hash' => hash('sha256', $activationToken),
        ]);

        // Also authorize this browser to access its order if the query string is lost.
        $request->session()->put("bmlk_orders.{$order->id}", $order->access_token);
        $request->session()->put("bmlk_auto_login.{$order->id}", $order->access_token);

        $paymentUrl = app(StripeController::class)->checkoutUrl($order);

        if (! $paymentUrl) {
            // A failed first attempt is still an order: email it, and allow retry.
            // Never override an existing Stripe session or a confirmed payment.
            if (config('services.stripe.secret')) {
                Order::query()->whereKey($order->id)
                    ->where('payment_status', 'unpaid')
                    ->whereNull('stripe_session_id')
                    ->update([
                        'payment_status' => 'failed',
                        'payment_failed_at' => now(),
                    ]);
            }

            return response()->json([
                'success' => false,
                'message' => __('Le paiement est temporairement indisponible. Votre commande est enregistrée, veuillez réessayer.'),
                'retry_url' => route('order.pay', [
                    'orderNumber' => $order->order_number,
                    'token' => $order->access_token,
                ]),
                'order_number' => $order->order_number,
                'status_url' => route('order.status', ['orderNumber' => $order->order_number, 'token' => $order->access_token]),
                'onboarding_token' => $activationToken,
            ], 503);
        }

        return response()->json([
            'success' => true,
            'redirect' => $paymentUrl,
            'order_number' => $order->order_number,
            'status_url' => route('order.status', ['orderNumber' => $order->order_number, 'token' => $order->access_token]),
            'onboarding_token' => $activationToken,
        ]);
    }

    public function promo(string $code): JsonResponse
    {
        $influencer = User::query()
            ->where('influencer_code', Str::upper($code))
            ->where('is_influencer', true)
            ->whereNotNull('email_verified_at')
            ->first();

        if (! $influencer) {
            return response()->json(['message' => __('Code promotionnel invalide.')], 404);
        }

        return response()->json(['code' => $influencer->influencer_code, 'percent' => (float) $influencer->influencer_discount]);
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

    public function recoveryForm(Request $request, ?string $orderNumber = null): View
    {
        return view('shop.recover-order', [
            'orderNumber' => $orderNumber ?: (string) $request->query('order', ''),
            'metaTitle' => __('Reprendre une commande | BLACK MILK'),
        ]);
    }

    public function recoverOrder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_number' => ['required', 'string', 'regex:/^BM-[0-9]{8}-[0-9]{6}$/'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $order = Order::query()->where('order_number', $data['order_number'])->first();

        if (! $order || ! $order->access_token || (
            filled($order->email) && ! hash_equals(
                Str::lower(trim($order->email)),
                Str::lower(trim($data['email'] ?? '')),
            )
        )) {
            throw ValidationException::withMessages([
                'order_number' => __('Commande introuvable ou informations incorrectes.'),
            ]);
        }

        $request->session()->put("bmlk_orders.{$order->id}", $order->access_token);

        return redirect()->route('order.status', [
            'orderNumber' => $order->order_number,
            'token' => $order->access_token,
        ]);
    }

    public function orderStatus(Request $request, string $orderNumber): View|RedirectResponse
    {
        $order = Order::query()->where('order_number', $orderNumber)->first();

        $token = (string) $request->query('token', '');
        $sessionToken = $order
            ? (string) $request->session()->get("bmlk_orders.{$order->id}", '')
            : '';

        $authorized = $order && $order->access_token && (
            hash_equals($order->access_token, $token) ||
            hash_equals($order->access_token, $sessionToken)
        );

        if (! $authorized && $order && $order->access_token && blank($order->email)) {
            // Older orders without an email can be resumed using their order number.
            $request->session()->put("bmlk_orders.{$order->id}", $order->access_token);
            $authorized = true;
        }

        if (! $authorized) {
            return $this->recoveryForm($request, $orderNumber);
        }

        if ($order->payment_status === 'paid') {
            return redirect()->route('account.checkout', [
                'orderNumber' => $order->order_number,
                'token' => $order->access_token,
            ]);
        }

        return view('shop.order-status', [
            'order' => $order,
            'metaTitle' => __('Commande :number | BLACK MILK', [
                'number' => $order->order_number,
            ]),
        ]);
    }

    public function normalizeItems(array $cartItems): Collection
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
                'image' => $product->storefrontImageUrl('thumb'),
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
