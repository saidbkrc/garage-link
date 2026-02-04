<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scene extends Model
{
    use HasFactory;

    protected $fillable = [
        'dealer_id',
        'name',
        'icon',
        'color',
        'actions',
        'order',
        'is_active',
    ];

    protected $casts = [
        'actions' => 'array',
        'is_active' => 'boolean',
    ];

    public function dealer()
    {
        return $this->belongsTo(Dealer::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
}
