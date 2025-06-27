<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Queue extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'type', 'max_quantity', 'remaining_quantity', 'status', 'current_number'
    ];

    public function entries() {
        return $this->hasMany(QueueEntry::class);
    }

    public function cashiers() {
        return $this->hasMany(Cashier::class, 'assigned_queue_id');
    }
}