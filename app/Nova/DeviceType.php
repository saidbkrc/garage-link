<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Http\Requests\NovaRequest;

class DeviceType extends Resource
{
    public static $model = \App\Models\DeviceType::class;

    public static $title = 'name';

    public static $search = ['id', 'name', 'slug'];

    public static function label()
    {
        return 'Cihaz Tipleri';
    }

    public static function singularLabel()
    {
        return 'Cihaz Tipi';
    }

    public function fields(NovaRequest $request)
    {
        return [
            ID::make()->sortable(),

            Text::make('Ad', 'name')
                ->sortable()
                ->rules('required', 'max:255'),

            Text::make('Slug', 'slug')
                ->sortable()
                ->rules('required', 'max:255')
                ->creationRules('unique:device_types,slug')
                ->updateRules('unique:device_types,slug,{{resourceId}}'),

            Text::make('İkon', 'icon')
                ->nullable()
                ->hideFromIndex(),

            Code::make('Varsayılan Komutlar', 'default_commands')
                ->json()
                ->nullable()
                ->hideFromIndex(),

            Boolean::make('Aktif', 'is_active')
                ->sortable()
                ->default(true),

            HasMany::make('Cihazlar', 'devices', Device::class),
            HasMany::make('Komutlar', 'commands', Command::class),
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
