<?php

namespace App\Events;

use App\Models\Queue;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockDepleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $queue;

    /**
     * Create a new event instance.
     */
    public function __construct(Queue $queue)
    {
        $this->queue = $queue;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('queue.' . $this->queue->id),
            new Channel('queues'),
            new Channel('alerts'),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'queue_id' => $this->queue->id,
            'queue_name' => $this->queue->name,
            'type' => 'stock_depleted',
            'message' => 'Stock has been depleted for queue: ' . $this->queue->name,
            'remaining_quantity' => $this->queue->remaining_quantity,
            'max_quantity' => $this->queue->max_quantity,
            'timestamp' => now(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'stock.depleted';
    }
}
