<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QueueEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'queue_id', 
        'customer_name', 
        'phone_number', 
        'order_details', 
        'queue_number', 
        'quantity_purchased', 
        'estimated_wait_time', 
        'notes', 
        'cashier_id', 
        'order_status'
    ];

    protected $casts = [
        'order_details' => 'array',
        'estimated_wait_time' => 'integer',
    ];

    public function queue() {
        return $this->belongsTo(Queue::class);
    }

    public function cashier() {
        return $this->belongsTo(Cashier::class);
    }

    public function tracking() {
        return $this->hasOne(CustomerTracking::class);
    }
}