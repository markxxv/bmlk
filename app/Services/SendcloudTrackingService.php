<?php

namespace App\Services;

use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class SendcloudTrackingService
{
    private const API = 'https://panel.sendcloud.sc/api/v3/parcels/tracking/';

    public function sync(Order $order): int
    {
        $trackingNumber = trim((string) $order->tracking_number);
        if ($trackingNumber === '') {
            return 0;
        }

        $public = (string) config('services.sendcloud.public_key');
        $secret = (string) config('services.sendcloud.secret_key');
        if ($public === '' || $secret === '') {
            throw new RuntimeException('Sendcloud API credentials are missing.');
        }

        $response = Http::acceptJson()
            ->withBasicAuth($public, $secret)
            ->connectTimeout(5)
            ->timeout(20)
            ->get(self::API.rawurlencode($trackingNumber));

        if (! $response->successful()) {
            throw new RuntimeException('Sendcloud tracking API returned HTTP '.$response->status().'.');
        }

        $data = $response->json();
        if (! is_array($data) || ! is_array($data['events'] ?? null)) {
            throw new RuntimeException('Sendcloud returned an invalid tracking event response.');
        }

        $count = 0;
        $events = $data['events'];

        // The announcement date is explicitly the label creation date, not a
        // carrier handover. Preserve it as a separate timeline milestone.
        if (is_string($data['announced_at'] ?? null) && $data['announced_at'] !== '') {
            $events[] = [
                'event_at' => $data['announced_at'],
                'event_type' => 'internal',
                'phase' => 'label_created',
                'description' => 'Shipping label created',
                'status_type' => 'success',
            ];
        }

        foreach ($events as $event) {
            if (! is_array($event)) {
                continue;
            }

            $count += $this->storeEvent($order, $event, $trackingNumber) ? 1 : 0;
        }

        // Never assign a new tracking number from an unrelated API response.
        $tracking = collect($data['tracking_numbers'] ?? [])
            ->first(fn ($item) => is_array($item) && ($item['tracking_number'] ?? null) === $trackingNumber);
        $url = is_array($tracking) ? ($tracking['tracking_url'] ?? null) : null;
        $url = is_string($url) && filter_var($url, FILTER_VALIDATE_URL)
            && parse_url($url, PHP_URL_SCHEME) === 'https' ? $url : null;

        $latest = $order->trackingEvents()->where('tracking_number', $trackingNumber)
            ->where('phase', '!=', 'label_created')
            ->orderByDesc('event_at')->orderByDesc('id')->first();

        $attributes = [
            'sendcloud_tracking_synced_at' => now(),
            'sendcloud_expected_delivery_date' => data_get($data, 'details.expected_delivery_date'),
        ];

        if ($url) {
            $attributes['tracking_url'] = $url;
        }

        if ($latest) {
            $attributes['sendcloud_tracking_phase'] = $latest->phase;
            $attributes['sendcloud_status'] = $latest->description ?: $latest->phase;

            if ($latest->phase === 'delivered'
                && ! in_array($order->status, ['cancelled', 'archived', 'completed'], true)
                && $order->payment_status === 'paid') {
                $attributes['status'] = 'completed';
            }
        }

        $order->forceFill($attributes)->save();

        return $count;
    }

    /**
     * Store either a v3 API event (event_at) or a v3 event subscription payload
     * (timestamp). Fingerprints are stable between sources and repeated syncs.
     */
    public function storeEvent(Order $order, array $event, ?string $trackingNumber = null): bool
    {
        $trackingNumber ??= trim((string) $order->tracking_number);
        $eventType = (string) ($event['event_type'] ?? 'carrier');
        if ($trackingNumber === '' || ! in_array($eventType, ['carrier', 'calculated', 'internal'], true)) {
            return false;
        }

        $rawTime = $event['event_at'] ?? $event['timestamp'] ?? null;
        if (! is_string($rawTime) || trim($rawTime) === '') {
            return false;
        }

        try {
            $time = CarbonImmutable::parse($rawTime)->utc();
        } catch (Throwable) {
            return false;
        }

        $phase = trim((string) ($event['phase'] ?? ''));
        $subStatus = trim((string) ($event['sub_status'] ?? ''));
        $exception = trim((string) ($event['exception'] ?? ''));
        $description = trim((string) ($event['description'] ?? ''));
        $locationDetails = $event['location_details'] ?? null;
        $location = is_array($locationDetails)
            ? implode(', ', array_filter([
                $locationDetails['city'] ?? null,
                $locationDetails['country'] ?? null,
            ], fn ($part) => is_string($part) && $part !== ''))
            : null;

        // Do not put translated descriptions in the deduplication key.
        // Sendcloud can update carrier descriptions after the initial event.
        $key = hash('sha256', implode('|', [
            $trackingNumber,
            $time->toIso8601String(),
            $eventType,
            $phase,
            $subStatus,
            $exception,
        ]));

        DB::table('order_tracking_events')->upsert([
            'order_id' => $order->id,
            'tracking_number' => $trackingNumber,
            'event_key' => $key,
            'event_at' => $time->toDateTimeString(),
            'event_type' => $eventType,
            'phase' => mb_substr($phase, 0, 64),
            'exception' => mb_substr($exception, 0, 64),
            'sub_status' => mb_substr($subStatus, 0, 100),
            'status_type' => mb_substr((string) ($event['status_type'] ?? ''), 0, 32),
            'description' => mb_substr($description, 0, 4000),
            'location' => $location ? mb_substr($location, 0, 255) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ], ['order_id', 'event_key'], [
            'description', 'location', 'status_type', 'updated_at',
        ]);

        return true;
    }
}
