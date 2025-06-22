<?php

namespace App\Notifications;

use App\Models\QueueEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $entry;
    public $status;

    /**
     * Create a new notification instance.
     */
    public function __construct(QueueEntry $entry, string $status)
    {
        $this->entry = $entry;
        $this->status = $status;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $statusMessages = [
            'queued' => 'Your order has been queued',
            'kitchen' => 'Your order is now in the kitchen',
            'preparing' => 'Your order is being prepared',
            'serving' => 'Your order is ready for pickup',
            'completed' => 'Your order has been completed',
            'cancelled' => 'Your order has been cancelled',
        ];

        $message = $statusMessages[$this->status] ?? 'Your order status has been updated';

        return (new MailMessage)
            ->subject("Order Status Update - {$this->entry->queue->name}")
            ->greeting("Hello!")
            ->line($message)
            ->line("Queue Number: {$this->entry->queue_number}")
            ->line("Queue: {$this->entry->queue->name}")
            ->line("Status: " . ucfirst($this->status))
            ->action('Track Your Order', url("/api/tracking/{$this->entry->id}"))
            ->line('Thank you for using our service!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'entry_id' => $this->entry->id,
            'queue_number' => $this->entry->queue_number,
            'queue_name' => $this->entry->queue->name,
            'status' => $this->status,
            'message' => "Order #{$this->entry->queue_number} status updated to {$this->status}",
            'tracking_url' => url("/api/tracking/{$this->entry->id}"),
        ];
    }

    /**
     * Get the notification's database type.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'entry_id' => $this->entry->id,
            'queue_number' => $this->entry->queue_number,
            'queue_name' => $this->entry->queue->name,
            'status' => $this->status,
            'message' => "Order #{$this->entry->queue_number} status updated to {$this->status}",
            'tracking_url' => url("/api/tracking/{$this->entry->id}"),
            'created_at' => now(),
        ];
    }
}
