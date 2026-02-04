<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dealer extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(DealerUser::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function scenes()
    {
        return $this->hasMany(Scene::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }

    public function energyUsages()
    {
        return $this->hasMany(EnergyUsage::class);
    }
}
