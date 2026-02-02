<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Http\Requests\NovaRequest;

class DeviceLog extends Resource
{
    public static $model = \App\Models\DeviceLog::class;

    public static $title = 'id';

    public static $search = ['id'];

    public static function label()
    {
        return 'Cihaz Logları';
    }

    public static function singularLabel()
    {
        return 'Cihaz Logu';
    }

    public function fields(NovaRequest $request)
    {
        return [
            ID::make()->sortable(),

            BelongsTo::make('Cihaz', 'device', Device::class)
                ->sortable(),

            BelongsTo::make('Komut', 'command', Command::class)
                ->nullable()
                ->sortable(),

            BelongsTo::make('Kullanıcı', 'dealerUser', DealerUser::class)
                ->nullable()
                ->sortable(),

            Select::make('Kaynak', 'source')
                ->options([
                    'app' => 'Mobil Uygulama',
                    'panel' => 'Web Panel',
                    'schedule' => 'Zamanlayıcı',
                    'system' => 'Sistem',
                ])
                ->sortable(),

            Badge::make('Durum', 'status')
                ->map([
                    'pending' => 'warning',
                    'success' => 'success',
                    'failed' => 'danger',
                ])
                ->sortable(),

            Code::make('İstek', 'request_payload')
                ->json()
                ->nullable(),

            Code::make('Cevap', 'response')
                ->json()
                ->nullable(),

            DateTime::make('Tarih', 'created_at')
                ->sortable()
                ->exceptOnForms(),
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
