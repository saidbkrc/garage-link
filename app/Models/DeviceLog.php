<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceLog extends Model
{
    protected $fillable = [
        'device_id',
        'command_id',
        'dealer_user_id',
        'source',
        'request_payload',
        'response',
        'status',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response' => 'array',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function command(): BelongsTo
    {
        return $this->belongsTo(Command::class);
    }

    public function dealerUser(): BelongsTo
    {
        return $this->belongsTo(DealerUser::class);
    }
}