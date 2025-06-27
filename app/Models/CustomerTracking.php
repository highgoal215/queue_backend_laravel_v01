<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerTracking extends Model
{
    use HasFactory;

    protected $table = 'customer_tracking';
    
    protected $fillable = [
        'queue_entry_id', 
        'qr_code_url', 
        'status', 
        'estimated_wait_time', 
        'current_position', 
        'last_updated'
    ];

    public function entry() {
        return $this->belongsTo(QueueEntry::class, 'queue_entry_id');
    }
}