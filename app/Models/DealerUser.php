<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DealerUser extends Model
{
    protected $fillable = [
        'dealer_id',
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function deviceLogs(): HasMany
    {
        return $this->hasMany(DeviceLog::class);
    }
}