<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingBox;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class SendcloudController extends Controller
{
    private const API = 'https://panel.sendcloud.sc/api/v3';

    private const EU = ['AT','BE','BG','HR','CY','CZ','DK','EE','FI','FR','DE','GR','HU','IE','IT','LV','LT','LU','MT','NL','PL','PT','RO','SK','SI','ES','SE'];

    // ISO 3166-1 alpha-2. Names come from PHP Intl when available.
    private const ISO = 'AD,AE,AF,AG,AI,AL,AM,AO,AQ,AR,AS,AT,AU,AW,AX,AZ,BA,BB,BD,BE,BF,BG,BH,BI,BJ,BL,BM,BN,BO,BQ,BR,BS,BT,BV,BW,BY,BZ,CA,CC,CD,CF,CG,CH,CI,CK,CL,CM,CN,CO,CR,CU,CV,CW,CX,CY,CZ,DE,DJ,DK,DM,DO,DZ,EC,EE,EG,EH,ER,ES,ET,FI,FJ,FK,FM,FO,FR,GA,GB,GD,GE,GF,GG,GH,GI,GL,GM,GN,GP,GQ,GR,GS,GT,GU,GW,GY,HK,HM,HN,HR,HT,HU,ID,IE,IL,IM,IN,IO,IQ,IR,IS,IT,JE,JM,JO,JP,KE,KG,KH,KI,KM,KN,KP,KR,KW,KY,KZ,LA,LB,LC,LI,LK,LR,LS,LT,LU,LV,LY,MA,MC,MD,ME,MF,MG,MH,MK,ML,MM,MN,MO,MP,MQ,MR,MS,MT,MU,MV,MW,MX,MY,MZ,NA,NC,NE,NF,NG,NI,NL,NO,NP,NR,NU,NZ,OM,PA,PE,PF,PG,PH,PK,PL,PM,PN,PR,PS,PT,PW,PY,QA,RE,RO,RS,RU,RW,SA,SB,SC,SD,SE,SG,SH,SI,SJ,SK,SL,SM,SN,SO,SR,SS,ST,SV,SX,SY,SZ,TC,TD,TF,TG,TH,TJ,TK,TL,TM,TN,TO,TR,TT,TV,TW,TZ,UA,UG,UM,US,UY,UZ,VA,VC,VE,VG,VI,VN,VU,WF,WS,YE,YT,ZA,ZM,ZW';

    public static function countries(): array
    {
        $countries = [];
        foreach (explode(',', self::ISO) as $code) {
            $countries[$code] = class_exists(\Locale::class)
                ? (\Locale::getDisplayRegion('und-'.$code, 'fr') ?: $code)
                : $code;
        }
        $countries['FR'] = 'France';
        asort($countries, SORT_NATURAL | SORT_FLAG_CASE);
        return ['FR' => 'France'] + $countries;
    }

    public function zone(string $country): Delivery
    {
        $country = strtoupper($country);
        if (! array_key_exists($country, self::countries())) {
            throw ValidationException::withMessages(['country' => 'Pays de livraison invalide.']);
        }

        $code = match (true) {
            $country === 'FR' => 'fr',
            in_array($country, ['BE','NL','DE','LU','ES','IT','PT','AT'], true) => 'eu1',
            in_array($country, self::EU, true) => 'eu2',
            default => 'int',
        };

        $delivery = Delivery::query()->where('code', $code)->where('active', true)->first();
        if (! $delivery) {
            throw ValidationException::withMessages(['country' => 'Livraison indisponible pour ce pays.']);
        }
        return $delivery;
    }

    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        $public = (string) config('services.sendcloud.public_key');
        $secret = (string) config('services.sendcloud.secret_key');
        if ($public === '' || $secret === '') {
            throw new RuntimeException('Sendcloud API credentials missing.');
        }
        return Http::acceptJson()->asJson()->withBasicAuth($public, $secret)
            ->connectTimeout(4)->timeout(12);
    }

    private function call(string $method, string $uri, ?array $data = null): array
    {
        $response = $this->client()->send($method, self::API.$uri, $data === null ? [] : ['json' => $data]);
        if (! $response->successful()) {
            Log::warning('Sendcloud API error', [
                'path' => $uri, 'status' => $response->status(),
                'errors' => $response->json('errors') ?? $response->json('message'),
            ]);
            throw new RuntimeException('Sendcloud HTTP '.$response->status());
        }
        return $response->json() ?? [];
    }

    // Returns one physical parcel. A conservative volume reserve is used; actual boxes stay editable in Filament.
    public function parcel(Collection|array $items): array
    {
        $items = collect($items);
        $products = Product::query()->with('category')
            ->whereIn('id', $items->pluck('product_id')->unique())->get()->keyBy('id');

        $weight = 0;
        $volume = 0.0;
        $largest = [0.0, 0.0, 0.0];

        foreach ($items as $item) {
            $product = $products->get((int) ($item['product_id'] ?? 0));
            if (! $product) {
                throw ValidationException::withMessages(['cart_items' => 'Produit introuvable pour la livraison.']);
            }

            $values = [];
            foreach (['weight', 'height', 'depth', 'length'] as $key) {
                $field = 'delivery_'.$key;
                $value = $product->{$field} ?? $product->category?->{$field};
                if ($value === null || (float) $value <= 0) {
                    throw ValidationException::withMessages([
                        'cart_items' => 'Poids ou dimensions de livraison manquants : '.$product->getTranslation('name', 'fr'),
                    ]);
                }
                $values[$key] = (float) $value;
            }

            $qty = (int) ($item['quantity'] ?? 0);
            if ($qty < 1 || $qty > 99) {
                throw ValidationException::withMessages(['cart_items' => 'Quantité invalide.']);
            }
            $weight += (int) round($values['weight']) * $qty;
            $volume += $values['height'] * $values['depth'] * $values['length'] * $qty;
            $sides = [$values['height'], $values['depth'], $values['length']];
            rsort($sides, SORT_NUMERIC);
            foreach ($sides as $i => $side) {
                $largest[$i] = max($largest[$i], $side);
            }
        }

        $box = ShippingBox::ready()->get()->filter(function (ShippingBox $box) use ($weight, $volume, $largest): bool {
            $inside = [(float) $box->inner_length_cm, (float) $box->inner_width_cm, (float) $box->inner_height_cm];
            rsort($inside, SORT_NUMERIC);
            if ($box->max_weight_g && $weight > $box->max_weight_g) {
                return false;
            }
            return $inside[0] >= $largest[0] && $inside[1] >= $largest[1] && $inside[2] >= $largest[2]
                && array_product($inside) >= $volume * 1.3;
        })->sortBy(fn (ShippingBox $box): float =>
            (float) $box->outer_length_cm * (float) $box->outer_width_cm * (float) $box->outer_height_cm
        )->first();

        if (! $box) {
            throw ValidationException::withMessages(['cart_items' => 'Aucun carton adapté à ce panier.']);
        }

        return [
            'box_id' => $box->id,
            'box_name' => $box->name,
            'total_weight_g' => $weight + $box->tare_weight_g,
            'dimensions' => [
                'length' => (string) $box->outer_length_cm,
                'width' => (string) $box->outer_width_cm,
                'height' => (string) $box->outer_height_cm,
                'unit' => 'cm',
            ],
            'weight' => [
                'value' => number_format(($weight + $box->tare_weight_g) / 1000, 3, '.', ''),
                'unit' => 'kg',
            ],
        ];
    }

    public function options(Collection|array $items, string $country, string $zip, string $city, string $address, float $subtotal): array
    {
        $zone = $this->zone($country);
        $parcel = $this->parcel($items);

        try {
            $response = $this->call('POST', '/shipping-options', [
                'from_address' => [
                    'country_code' => 'FR', 'postal_code' => '77400',
                    'city' => 'Lagny-sur-Marne', 'address_line_1' => 'Avenue du Beau Site',
                    'house_number' => '31',
                ],
                'to_address' => [
                    'country_code' => strtoupper($country),
                    'postal_code' => $zip,
                    'city' => $city,
                    'address_line_1' => $address,
                ],
                'parcels' => [
                    ['dimensions' => $parcel['dimensions'], 'weight' => $parcel['weight']],
                ],
                'calculate_quotes' => true,
            ]);
        } catch (Throwable $e) {
            Log::warning('Sendcloud quotation failed', ['error' => $e->getMessage()]);
            throw ValidationException::withMessages(['shipping_option' => 'Calcul de livraison indisponible. Réessayez.']);
        }

        $free = $zone->free_from !== null && $subtotal >= (float) $zone->free_from;
        $all = collect($response['data'] ?? [])
            ->filter(function (array $option): bool {
                $f = $option['functionalities'] ?? [];
                $quote = $option['quotes'][0] ?? [];
                return ($f['b2c'] ?? false) === true
                    && ($f['tracked'] ?? false) === true
                    && ($f['form_factor'] ?? '') === 'parcel'
                    && ! empty($option['contract']['id'])
                    && empty($option['quote_error'])
                    && empty($quote['estimated_surcharges'])
                    && (float) data_get($quote, 'price.total.value', 0) > 0
                    && data_get($quote, 'price.total.currency') === 'EUR';
            })
            ->map(function (array $option) use ($free): array {
                $f = $option['functionalities'];
                $type = in_array($f['last_mile'] ?? '', ['service_point','locker','locker_or_service_point'], true)
                    ? 'pickup'
                    : (($f['priority'] ?? '') === 'express' || ($f['delivery_before'] ?? null) !== null ? 'express' : 'home');
                $raw = (float) data_get($option, 'quotes.0.price.total.value');
                return [
                    'code' => $option['code'],
                    'contract_id' => (int) $option['contract']['id'],
                    'carrier_code' => $option['carrier']['code'],
                    'carrier' => $option['carrier']['name'],
                    'name' => $option['name'],
                    'type' => $type,
                    'actual_price' => round($raw, 2),
                    'price' => $free && $type !== 'express' ? 0.0 : round($raw, 2),
                    'lead_time' => data_get($option, 'quotes.0.lead_time'),
                    'requires_point' => (bool) ($option['requirements']['is_service_point_required'] ?? false),
                ];
            })
            ->sortBy('actual_price')
            ->values();

        // Shortlist avoids tens of duplicate carrier services in the checkout.
        $options = $all->where('type', 'home')->take(2)
            ->concat($all->where('type', 'pickup')->take(2))
            ->concat($all->where('type', 'express')->take(1))->values()->all();

        if (empty($options)) {
            throw ValidationException::withMessages(['shipping_option' => 'Aucun transporteur disponible pour cette adresse.']);
        }

        return [
            'zone' => $zone,
            'free' => $free,
            'parcel' => $parcel,
            'options' => $options,
        ];
    }

    public function quote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'country' => ['required','string','size:2'],
            'zip' => ['required','string','max:30'],
            'city' => ['required','string','max:120'],
            'address' => ['required','string','max:1000'],
            'cart_items' => ['required','array','min:1'],
            'cart_items.*.product_id' => ['required','integer'],
            'cart_items.*.quantity' => ['required','integer','min:1','max:99'],
        ]);

        // Server revalidates prices and product options; never trust cart totals from JS.
        $items = app(ShopController::class)->normalizeItems($validated['cart_items']);
        $subtotal = round($items->sum(fn (array $item): float => $item['line_total']), 2);
        $result = $this->options($items, strtoupper($validated['country']), $validated['zip'], $validated['city'], $validated['address'], $subtotal);

        return response()->json([
            'options' => $result['options'],
            'free_from' => $result['zone']->free_from !== null ? (float) $result['zone']->free_from : null,
            'free' => $result['free'],
        ])->header('Cache-Control', 'no-store');
    }

    public function points(Request $request): JsonResponse
    {
        $v = $request->validate([
            'country' => ['required','string','size:2'],
            'zip' => ['required','string','max:30'],
            'city' => ['required','string','max:120'],
            'carrier' => ['required','string','regex:/^[a-z_]+$/'],
        ]);
        try {
            $response = $this->client()->get(self::API.'/service-points', [
                'country_code' => strtoupper($v['country']),
                'carrier_code' => $v['carrier'],
                'address' => $v['zip'].' '.$v['city'],
                'limit' => 15,
            ]);
            if (! $response->successful()) {
                throw new RuntimeException('Service point lookup failed.');
            }
            $points = collect($response->json('data.results', []))
                ->map(fn (array $p): array => [
                    'id' => $p['id'],
                    'name' => $p['name'],
                    'carrier' => data_get($p, 'carrier.code'),
                    'address' => trim(implode(' ', array_filter([
                        data_get($p, 'address.street'),
                        data_get($p, 'address.house_number'),
                        data_get($p, 'address.postal_code'),
                        data_get($p, 'address.city'),
                    ]))),
                ])->values()->all();
            return response()->json(['points' => $points]);
        } catch (Throwable $e) {
            Log::warning('Sendcloud service points unavailable', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Points relais momentanément indisponibles.'], 503);
        }
    }

    public function pointDetails(int $id, string $country, string $carrier): array
    {
        try {
            $point = data_get($this->call('GET', '/service-points/'.$id), 'data', []);
        } catch (Throwable $e) {
            throw ValidationException::withMessages(['service_point_id' => 'Point relais indisponible.']);
        }
        if ((int) ($point['id'] ?? 0) !== $id
            || strtoupper((string) data_get($point, 'address.country_code')) !== strtoupper($country)
            || (string) data_get($point, 'carrier.code') !== $carrier
            || ($point['is_expired'] ?? false)) {
            throw ValidationException::withMessages(['service_point_id' => 'Point relais invalide.']);
        }
        return [
            'id' => $id,
            'name' => (string) ($point['name'] ?? ''),
            'address' => trim(implode(' ', array_filter([
                data_get($point, 'address.street'),
                data_get($point, 'address.house_number'),
                data_get($point, 'address.postal_code'),
                data_get($point, 'address.city'),
            ]))),
        ];
    }

    private function integrationId(): int
    {
        return Cache::remember('sendcloud.integration_id', now()->addHours(12), function (): int {
            $integrations = collect($this->call('GET', '/integrations')['data'] ?? []);
            $match = $integrations->first(fn (array $i): bool =>
                ($i['type'] ?? null) === 'api'
                && str_contains(strtolower((string) ($i['webhook_url'] ?? '')), '/api/webhooks/sendcloud')
            ) ?? $integrations->first(fn (array $i): bool => ($i['type'] ?? null) === 'api');
            if (! $match) {
                throw new RuntimeException('Sendcloud API integration not found.');
            }
            return (int) $match['id'];
        });
    }

    // Safe to repeat: Sendcloud upserts by order_id + integration ID.
    public function syncPaidOrder(Order $order): void
    {
        if ($order->payment_status !== 'paid' || $order->sendcloud_order_id) {
            return;
        }
        try {
            $package = $order->shipping_parcel;
            if (! is_array($package)) {
                throw new RuntimeException('Missing confirmed parcel.');
            }
            $payload = [[
                'order_id' => (string) $order->id,
                'order_number' => $order->order_number,
                'order_details' => [
                    'integration' => ['id' => $this->integrationId()],
                    'status' => ['code' => 'processing', 'message' => 'Preparing'],
                    'order_created_at' => $order->created_at->toIso8601String(),
                    'order_items' => collect($order->items)->map(fn (array $item): array => [
                        'name' => $item['name'],
                        'quantity' => $item['quantity'],
                        'total_price' => ['value' => (float) $item['line_total'], 'currency' => 'EUR'],
                    ])->all(),
                ],
                'payment_details' => [
                    'total_price' => ['value' => (float) $order->total_amount, 'currency' => 'EUR'],
                    'status' => ['code' => 'paid', 'message' => 'Paid'],
                ],
                'shipping_address' => [
                    'name' => trim($order->first_name.' '.$order->last_name),
                    'address_line_1' => $order->address,
                    'postal_code' => $order->zip,
                    'city' => $order->city,
                    'country_code' => $order->shipping_country_code,
                ],
                'shipping_details' => [
                    'is_local_pickup' => false,
                    'delivery_indicator' => $order->shipping_option_name,
                    'measurement' => ['weight' => $package['weight']],
                ],
            ]];
            if ($order->service_point_id) {
                $payload[0]['service_point_details'] = ['id' => (string) $order->service_point_id];
            }

            $result = $this->call('POST', '/orders', $payload);
            $id = data_get($result, 'data.0.id');
            if (! $id) {
                throw new RuntimeException('Sendcloud returned no order ID.');
            }
            $order->forceFill(['sendcloud_order_id' => (string) $id])->save();
        } catch (Throwable $e) {
            Log::error('Sendcloud paid order sync failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }

    // Explicit admin action: label creation may incur a carrier charge.
    public function createLabel(Order $order): void
    {
        // Avoid simultaneous label purchases from multiple admin requests.
        $lock = Cache::lock('sendcloud-label-'.$order->id, 45);
        if (! $lock->get()) {
            throw new RuntimeException('Une étiquette est déjà en cours de création.');
        }
        try {
            $order->refresh();
            if ($order->tracking_number) {
                throw new RuntimeException('Une étiquette existe déjà.');
            }
        if ($order->payment_status !== 'paid' || $order->tracking_number) {
            throw new RuntimeException('Only paid orders without labels can be shipped.');
        }
        // International customs documents require HS codes, origin and export details.
        // Until the merchant has supplied them, use Sendcloud's panel for non-EU labels.
        if (! in_array($order->shipping_country_code, self::EU, true)) {
            throw new RuntimeException('Étiquette hors UE : complétez les formalités douanières dans Sendcloud.');
        }
        if (! $order->sendcloud_order_id) {
            $this->syncPaidOrder($order);
            $order->refresh();
        }
        if (! $order->sendcloud_order_id) {
            throw new RuntimeException('Order has not been accepted by Sendcloud.');
        }

        $result = $this->call('POST', '/orders/create-label-sync', [
            'integration_id' => $this->integrationId(),
            'sender_address_id' => 944950,
            'order' => ['order_number' => $order->order_number, 'apply_shipping_rules' => false],
            'ship_with' => [
                'type' => 'shipping_option_code',
                'properties' => [
                    'shipping_option_code' => $order->shipping_option_code,
                    'contract_id' => (int) $order->shipping_contract_id,
                ],
            ],
            'label_details' => ['mime_type' => 'application/pdf', 'dpi' => 72],
        ]);

        $data = data_get($result, 'data.0', []);
        if (empty($data['tracking_number'])) {
            throw new RuntimeException('Sendcloud label response has no tracking number.');
        }

        if ($encoded = data_get($data, 'label.file')) {
            $binary = base64_decode($encoded, true);
            if ($binary && str_starts_with($binary, '%PDF')) {
                Storage::disk('local')->put('sendcloud-labels/'.$order->id.'.pdf', $binary);
            }
        }

        $order->forceFill([
            'tracking_number' => $data['tracking_number'],
            'tracking_url' => $data['tracking_url'] ?? null,
            'sendcloud_parcel_id' => $data['parcel_id'] ?? null,
            'sendcloud_shipment_id' => $data['shipment_id'] ?? null,
            'status' => 'processing',
        ])->save();
        } finally {
            $lock->release();
        }
    }

    public function label(Order $order): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        abort_unless(auth()->user()?->is_admin === true, 403);
        abort_unless($order->tracking_number, 404);
        abort_unless(Storage::disk('local')->exists('sendcloud-labels/'.$order->id.'.pdf'), 404);
        return response()->download(Storage::disk('local')->path('sendcloud-labels/'.$order->id.'.pdf'), $order->order_number.'.pdf');
    }

    public function webhook(Request $request): JsonResponse
    {
        $secret = (string) config('services.sendcloud.webhook_secret');
        $signature = (string) $request->header('Sendcloud-Signature', '');
        if ($secret === '' || $signature === ''
            || ! hash_equals(hash_hmac('sha256', $request->getContent(), $secret), $signature)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $event = $request->json()->all();
        if (($event['action'] ?? null) !== 'parcel_status_changed') {
            return response()->json(['ok' => true]);
        }

        $parcel = $event['parcel'] ?? [];
        $order = Order::query()
            ->when(! empty($parcel['order_number']), fn ($q) => $q->where('order_number', $parcel['order_number']))
            ->when(empty($parcel['order_number']), fn ($q) => $q->where('sendcloud_parcel_id', $parcel['id'] ?? -1))
            ->first();

        if ($order && $order->payment_status === 'paid' && $order->status !== 'cancelled') {
            $timestamp = (int) ($event['carrier_status_change_timestamp'] ?? $event['timestamp'] ?? 0);
            if ($timestamp > (int) ($order->sendcloud_event_at ?? 0)) {
                $message = strtolower((string) data_get($parcel, 'status.message', ''));
                $status = $order->status;
                if (preg_match('/^(delivered|livr[ée]|distribu[ée])/iu', $message)) {
                    $status = 'completed';
                } elseif (str_contains($message, 'transit') || str_contains($message, 'en route') || str_contains($message, 'shipp')) {
                    $status = 'shipped';
                }
                $order->forceFill([
                    'sendcloud_event_at' => $timestamp,
                    'sendcloud_status' => data_get($parcel, 'status.message'),
                    'tracking_number' => $parcel['tracking_number'] ?? $order->tracking_number,
                    'tracking_url' => $parcel['tracking_url'] ?? $order->tracking_url,
                    'sendcloud_parcel_id' => $parcel['id'] ?? $order->sendcloud_parcel_id,
                    'status' => $status,
                ])->save();
            }
        }
        return response()->json(['ok' => true]);
    }
}
