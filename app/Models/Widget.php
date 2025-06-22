<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Widget extends Model
{
    protected $fillable = [
        'screen_layout_id', 'type', 'position', 'settings_json'
    ];

    protected $casts = [
        'settings_json' => 'array'
    ];

    public function layout() {
        return $this->belongsTo(ScreenLayout::class);
    }
}