<?php

namespace App\Events;

use App\Models\QueueEntry;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $entry;

    /**
     * Create a new event instance.
     */
    public function __construct(QueueEntry $entry)
    {
        $this->entry = $entry;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('queue.' . $this->entry->queue_id),
            new Channel('entry.' . $this->entry->id),
            new Channel('orders'),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'entry_id' => $this->entry->id,
            'queue_id' => $this->entry->queue_id,
            'queue_number' => $this->entry->queue_number,
            'order_status' => $this->entry->order_status,
            'queue_name' => $this->entry->queue->name,
            'cashier_name' => $this->entry->cashier ? $this->entry->cashier->name : null,
            'quantity_purchased' => $this->entry->quantity_purchased,
            'updated_at' => $this->entry->updated_at,
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'order.status.changed';
    }
}
