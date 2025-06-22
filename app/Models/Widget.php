<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Widget extends Model
{
    use HasFactory;

    protected $fillable = [
        'screen_layout_id', 'type', 'position', 'settings'
    ];

    protected $casts = [
        'settings' => 'array'
    ];

    public function layout() {
        return $this->belongsTo(ScreenLayout::class);
    }
}