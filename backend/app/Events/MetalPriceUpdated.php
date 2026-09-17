<?php

namespace App\Events;

use App\Models\MetalPrice;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired whenever an admin changes a metal rate, so every open storefront/
 * admin tab can update its displayed prices without a refresh or a poll.
 *
 * Rates are already public information (GET /metal-types needs no auth),
 * so this broadcasts on a public channel — no channel authorization needed.
 * Broadcasts synchronously (ShouldBroadcastNow, not ShouldBroadcast) so a
 * price update reaches listeners immediately without depending on a queue
 * worker being up.
 */
class MetalPriceUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public MetalPrice $metalPrice)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('metal-prices'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'metal-price.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'key' => $this->metalPrice->key,
            'label' => $this->metalPrice->label,
            'price_per_gram' => (string) $this->metalPrice->price_per_gram,
            'updated_at' => $this->metalPrice->updated_at?->toIso8601String(),
        ];
    }
}
