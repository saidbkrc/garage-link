<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class Dealer extends Resource
{
    public static $model = \App\Models\Dealer::class;

    public static $title = 'name';

    public static $search = ['id', 'name', 'email', 'phone'];

    public static function label()
    {
        return 'Bayiler';
    }

    public static function singularLabel()
    {
        return 'Bayi';
    }

    public function fields(NovaRequest $request)
    {
        return [
            ID::make()->sortable(),

            Text::make('Ad', 'name')
                ->sortable()
                ->rules('required', 'max:255'),

            Text::make('E-posta', 'email')
                ->sortable()
                ->rules('required', 'email', 'max:255')
                ->creationRules('unique:dealers,email')
                ->updateRules('unique:dealers,email,{{resourceId}}'),

            Text::make('Telefon', 'phone')
                ->sortable()
                ->nullable(),

            Textarea::make('Adres', 'address')
                ->nullable()
                ->hideFromIndex(),

            Boolean::make('Aktif', 'is_active')
                ->sortable()
                ->default(true),

            HasMany::make('Kullanıcılar', 'users', DealerUser::class),
            HasMany::make('Cihazlar', 'devices', Device::class),
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
