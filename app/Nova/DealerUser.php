<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Password;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Http\Requests\NovaRequest;

class DealerUser extends Resource
{
    public static $model = \App\Models\DealerUser::class;

    public static $title = 'name';

    public static $search = ['id', 'name', 'email'];

    public static function label()
    {
        return 'Bayi Kullanıcıları';
    }

    public static function singularLabel()
    {
        return 'Bayi Kullanıcısı';
    }

    public function fields(NovaRequest $request)
    {
        return [
            ID::make()->sortable(),

            BelongsTo::make('Bayi', 'dealer', Dealer::class)
                ->sortable()
                ->rules('required'),

            Text::make('Ad', 'name')
                ->sortable()
                ->rules('required', 'max:255'),

            Text::make('E-posta', 'email')
                ->sortable()
                ->rules('required', 'email', 'max:255')
                ->creationRules('unique:dealer_users,email')
                ->updateRules('unique:dealer_users,email,{{resourceId}}'),

            Password::make('Şifre', 'password')
                ->onlyOnForms()
                ->creationRules('required', 'min:6')
                ->updateRules('nullable', 'min:6'),

            Select::make('Rol', 'role')
                ->options([
                    'admin' => 'Admin',
                    'staff' => 'Personel',
                ])
                ->default('staff')
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
