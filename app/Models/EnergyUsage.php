<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnergyUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'dealer_id',
        'device_id',
        'date',
        'hour',
        'power_watt',
        'energy_kwh',
    ];

    protected $casts = [
        'date' => 'date',
        'power_watt' => 'decimal:2',
        'energy_kwh' => 'decimal:4',
    ];

    public function dealer()
    {
        return $this->belongsTo(Dealer::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
