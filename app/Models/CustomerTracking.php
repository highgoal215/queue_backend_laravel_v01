<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerTracking extends Model
{
    protected $table = 'customer_tracking';
    
    protected $fillable = [
        'queue_entry_id', 'qr_code_url'
    ];

    public function entry() {
        return $this->belongsTo(QueueEntry::class);
    }
}