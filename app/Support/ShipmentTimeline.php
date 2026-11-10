<?php

namespace App\Support;

use App\Models\Order;
use App\Models\OrderTrackingEvent;
use Illuminate\Support\Collection;

final class ShipmentTimeline
{
    public static function steps(Order $order): array
    {
        $events = self::events($order);
        $phases = [
            ['code' => 'label_created', 'label' => __('Étiquette créée')],
            ['code' => 'accepted', 'label' => __('Pris en charge')],
            ['code' => 'in_transit', 'label' => __('En transit')],
            ['code' => 'out_for_delivery', 'label' => __('En cours de livraison')],
            ['code' => 'delivered', 'label' => __('Livré')],
        ];

        $current = 0;
        $dates = [];
        foreach ($events as $event) {
            $step = self::stepForPhase($event->phase);
            if ($step === null) {
                continue;
            }
            $current = max($current, $step);
            if (! isset($dates[$step]) || $event->event_at->lt($dates[$step])) {
                $dates[$step] = $event->event_at;
            }
        }

        if (filled($order->tracking_number)) {
            $current = max(1, $current);
        }

        return array_map(fn (array $step, int $index): array => [
            ...$step,
            'state' => $index + 1 < $current ? 'done' : ($index + 1 === $current ? 'current' : 'future'),
            'date' => $dates[$index + 1] ?? null,
        ], $phases, array_keys($phases));
    }

    public static function events(Order $order): Collection
    {
        return $order->trackingEvents
            ->where('tracking_number', $order->tracking_number)
            ->sortByDesc(fn (OrderTrackingEvent $event) => $event->event_at->timestamp)
            ->values();
    }

    public static function label(?string $phase): string
    {
        return match ($phase) {
            'label_created', 'announced', 'ready_to_send' => __('Étiquette créée'),
            'accepted', 'handover', 'picked_up' => __('Pris en charge par le transporteur'),
            'in_transit', 'transport', 'transit' => __('En transit'),
            'out_for_delivery', 'last_mile', 'delivering' => __('En cours de livraison'),
            'delivered' => __('Colis livré'),
            'returned', 'returning' => __('Retour à l’expéditeur'),
            'failed', 'exception', 'delivery_failed' => __('Incident de livraison'),
            'available_for_pickup', 'at_service_point' => __('Disponible au point relais'),
            default => __('Mise à jour du transporteur'),
        };
    }

    private static function stepForPhase(?string $phase): ?int
    {
        return match ($phase) {
            'label_created', 'announced', 'ready_to_send' => 1,
            'accepted', 'handover', 'picked_up' => 2,
            'in_transit', 'transport', 'transit' => 3,
            'out_for_delivery', 'last_mile', 'delivering' => 4,
            'delivered' => 5,
            default => null,
        };
    }
}
