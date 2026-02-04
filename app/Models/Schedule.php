<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'dealer_id',
        'device_id',
        'scene_id',
        'name',
        'time',
        'days',
        'command_slug',
        'params',
        'is_active',
        'last_triggered_at',
    ];

    protected $casts = [
        'days' => 'array',
        'params' => 'array',
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
    ];

    public function dealer()
    {
        return $this->belongsTo(Dealer::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function scene()
    {
        return $this->belongsTo(Scene::class);
    }
}
