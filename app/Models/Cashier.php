<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cashier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'employee_id', 
        'status', 
        'assigned_queue_id', 
        'is_active', 
        'is_available', 
        'current_customer_id', 
        'total_served', 
        'average_service_time',
        'email',
        'phone',
        'role',
        'shift_start',
        'shift_end'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_available' => 'boolean',
        'shift_start' => 'datetime',
        'shift_end' => 'datetime',
    ];

    public function queue() {
        return $this->belongsTo(Queue::class, 'assigned_queue_id');
    }
}