<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Http\Requests\NovaRequest;

class Command extends Resource
{
    public static $model = \App\Models\Command::class;

    public static $title = 'name';

    public static $search = ['id', 'name', 'slug', 'category'];

    public static function label()
    {
        return 'Komutlar';
    }

    public static function singularLabel()
    {
        return 'Komut';
    }

    public function fields(NovaRequest $request)
    {
        return [
            ID::make()->sortable(),

            BelongsTo::make('Cihaz Tipi', 'deviceType', DeviceType::class)
                ->nullable()
                ->sortable(),

            Text::make('Ad', 'name')
                ->sortable()
                ->rules('required', 'max:255'),

            Text::make('Slug', 'slug')
                ->sortable()
                ->rules('required', 'max:255')
                ->creationRules('unique:commands,slug')
                ->updateRules('unique:commands,slug,{{resourceId}}'),

            Select::make('Kategori', 'category')
                ->options([
                    'basic' => 'Temel',
                    'group' => 'Grup',
                    'color' => 'Renk',
                    'scheduler' => 'Zamanlayıcı',
                    'scene' => 'Senaryo',
                    'scan' => 'Tarama',
                    'system' => 'Sistem',
                ])
                ->sortable()
                ->rules('required'),

            Text::make('MQTT Topic', 'mqtt_topic')
                ->default('pigasoft/commands')
                ->rules('required'),

            Code::make('Payload Şablonu', 'payload_template')
                ->json()
                ->rules('required')
                ->help('Örnek: {"turn_on": true, "device_index": "{device_index}"}'),

            Code::make('Zorunlu Parametreler', 'required_params')
                ->json()
                ->nullable()
                ->hideFromIndex()
                ->help('Örnek: ["device_index", "brightness"]'),

            Code::make('Opsiyonel Parametreler', 'optional_params')
                ->json()
                ->nullable()
                ->hideFromIndex(),

            Text::make('Cevap Topic', 'response_topic')
                ->nullable()
                ->hideFromIndex(),

            Text::make('İkon', 'icon')
                ->nullable()
                ->hideFromIndex(),

            Textarea::make('Açıklama', 'description')
                ->nullable()
                ->hideFromIndex(),

            Number::make('Sıra', 'order')
                ->default(0)
                ->sortable(),

            Boolean::make('Aktif', 'is_active')
                ->sortable()
                ->default(true),
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
