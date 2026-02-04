<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'dealer_id',
        'device_type_id',
        'room_id',
        'name',
        'mac_address',
        'mqtt_topic',
        'is_online',
        'last_seen_at',
        'state_updated_at',
        'settings',
        'current_state',
        'capabilities',
        'config',
        'energy_today',
        'energy_total',
        'is_active',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
        'state_updated_at' => 'datetime',
        'settings' => 'array',
        'current_state' => 'array',
        'capabilities' => 'array',
        'config' => 'array',
        'energy_today' => 'decimal:2',
        'energy_total' => 'decimal:2',
    ];

    public function dealer()
    {
        return $this->belongsTo(Dealer::class);
    }

    public function deviceType()
    {
        return $this->belongsTo(DeviceType::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function logs()
    {
        return $this->hasMany(DeviceLog::class);
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }

    public function energyUsages()
    {
        return $this->hasMany(EnergyUsage::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    // State güncelleme helper
    public function updateState(array $state)
    {
        $currentState = $this->current_state ?? [];
        $this->current_state = array_merge($currentState, $state);
        $this->state_updated_at = now();
        $this->save();
    }

    // Cihazın bir capability'si var mı?
    public function hasCapability(string $capability): bool
    {
        $capabilities = $this->capabilities ?? $this->deviceType?->capabilities ?? [];
        return in_array($capability, $capabilities);
    }
}
