<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Command extends Model
{
    protected $fillable = [
        'device_type_id',
        'name',
        'slug',
        'category',
        'mqtt_topic',
        'payload_template',
        'required_params',
        'optional_params',
        'response_topic',
        'icon',
        'description',
        'order',
        'is_active',
    ];

    protected $casts = [
        'payload_template' => 'array',
        'required_params' => 'array',
        'optional_params' => 'array',
        'is_active' => 'boolean',
    ];

    public function deviceType(): BelongsTo
    {
        return $this->belongsTo(DeviceType::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(DeviceLog::class);
    }

    /**
     * Payload şablonunu parametrelerle doldurur
     */
    public function buildPayload(array $params = []): array
    {
        $payload = $this->payload_template;
        
        array_walk_recursive($payload, function (&$value) use ($params) {
            if (is_string($value) && preg_match('/\{(\w+)\}/', $value, $matches)) {
                $key = $matches[1];
                if (isset($params[$key])) {
                    $value = $params[$key];
                }
            }
        });

        return $payload;
    }
}