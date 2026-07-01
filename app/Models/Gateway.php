<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gateway extends Model
{
    protected $fillable = [
        'dealer_id',
        'gateway_id',
        'name',
        'ip_address',
        'firmware_version',
        'is_online',
        'last_seen_at',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    /** last_seen bu süreden eskiyse gateway offline sayılır (dinleyici güncellemeyi bıraktıysa). */
    public const OFFLINE_AFTER_SECONDS = 120;

    /**
     * Dürüst online durumu: DB 'is_online' true OLSA BİLE last_seen çok eskiyse offline.
     * Firmware/dinleyici gateway'i açıkça offline'a çekmiyor; staleness ile gerçeği gösterir.
     */
    public function getIsOnlineAttribute($value): bool
    {
        $fresh = $this->last_seen_at
            && $this->last_seen_at->gt(now()->subSeconds(self::OFFLINE_AFTER_SECONDS));

        return (bool) $value && $fresh;
    }

    public function dealer()
    {
        return $this->belongsTo(Dealer::class);
    }

    public function devices()
    {
        return $this->hasMany(Device::class, 'gateway_db_id');
    }
}
