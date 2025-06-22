<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScreenLayout extends Model
{
    protected $fillable = [
        'name', 'device_id', 'layout_config', 'is_default'
    ];

    protected $casts = [
        'layout_config' => 'array'
    ];

    public function widgets() {
        return $this->hasMany(Widget::class);
    }
}