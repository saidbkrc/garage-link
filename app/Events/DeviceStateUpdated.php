<?php

namespace App\Events;

use App\Models\Device;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Bir cihazın durumu (power/brightness/color/online...) değiştiğinde
 * ilgili bayinin private kanalına yayınlanır. Panel bunu dinleyerek
 * arayüzü sayfa yenilemeden günceller.
 *
 * ShouldBroadcastNow: kuyruğa atmadan anında yayınlanır (queue worker gerektirmez).
 */
class DeviceStateUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $deviceId;
    public int $dealerId;
    public array $state;
    public bool $isOnline;

    public function __construct(Device $device)
    {
        $this->deviceId = $device->id;
        $this->dealerId = (int) $device->dealer_id;
        $this->state    = $device->current_state ?? [];
        $this->isOnline = (bool) $device->is_online;
    }

    /**
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('dealer.' . $this->dealerId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'device.state';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'device_id' => $this->deviceId,
            'state'     => $this->state,
            'is_online' => $this->isOnline,
        ];
    }
}
