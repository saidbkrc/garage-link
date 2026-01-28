<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeviceType extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'default_commands',
        'is_active',
    ];

    protected $casts = [
        'default_commands' => 'array',
        'is_active' => 'boolean',
    ];

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function commands(): HasMany
    {
        return $this->hasMany(Command::class);
    }
}