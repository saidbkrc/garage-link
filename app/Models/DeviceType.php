<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'category',
        'icon',
        'capabilities',
        'default_state',
        'config_schema',
        'default_commands',
        'is_active',
    ];

    protected $casts = [
        'capabilities' => 'array',
        'default_state' => 'array',
        'config_schema' => 'array',
        'default_commands' => 'array',
        'is_active' => 'boolean',
    ];

    public function devices()
    {
        return $this->hasMany(Device::class);
    }

    public function commands()
    {
        return $this->hasMany(Command::class);
    }
}
