<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Http\Requests\NovaRequest;

class Device extends Resource
{
    public static $model = \App\Models\Device::class;

    public static $title = 'name';

    public static $search = ['id', 'name', 'mac_address'];

    public static function label()
    {
        return 'Cihazlar';
    }

    public static function singularLabel()
    {
        return 'Cihaz';
    }

    public function fields(NovaRequest $request)
    {
        return [
            ID::make()->sortable(),

            BelongsTo::make('Bayi', 'dealer', Dealer::class)
                ->sortable()
                ->rules('required'),

            BelongsTo::make('Cihaz Tipi', 'deviceType', DeviceType::class)
                ->sortable()
                ->rules('required'),

            Text::make('Ad', 'name')
                ->sortable()
                ->rules('required', 'max:255'),

            Text::make('MAC Adresi', 'mac_address')
                ->sortable()
                ->rules('required', 'max:255')
                ->creationRules('unique:devices,mac_address')
                ->updateRules('unique:devices,mac_address,{{resourceId}}'),

            Text::make('MQTT Topic', 'mqtt_topic')
                ->nullable()
                ->hideFromIndex(),

            Boolean::make('Çevrimiçi', 'is_online')
                ->sortable()
                ->exceptOnForms(),

            DateTime::make('Son Görülme', 'last_seen_at')
                ->sortable()
                ->exceptOnForms(),

            Code::make('Ayarlar', 'settings')
                ->json()
                ->nullable()
                ->hideFromIndex(),

            Code::make('Mevcut Durum', 'current_state')
                ->json()
                ->nullable()
                ->hideFromIndex(),

            Boolean::make('Aktif', 'is_active')
                ->sortable()
                ->default(true),

            HasMany::make('Loglar', 'logs', DeviceLog::class),
        ];
    }

    public function cards(NovaRequest $request)
    {
        return [];
    }

    public function filters(NovaRequest $request)
    {
        return [];
    }

    public function lenses(NovaRequest $request)
    {
        return [];
    }

    public function actions(NovaRequest $request)
    {
        return [];
    }
}
