<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class DealerUser extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

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
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    public function dealer()
    {
        return $this->belongsTo(Dealer::class);
    }

    public function logs()
    {
        return $this->hasMany(DeviceLog::class);
    }

    /**
     * Admin mi kontrol et
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
