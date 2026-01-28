<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    protected $fillable = [
        'dealer_id',
        'device_type_id',
        'name',
        'mac_address',
        'mqtt_topic',
        'is_online',
        'last_seen_at',
        'settings',
        'current_state',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'current_state' => 'array',
        'is_online' => 'boolean',
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function deviceType(): BelongsTo
    {
        return $this->belongsTo(DeviceType::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(DeviceLog::class);
    }
}