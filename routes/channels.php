<?php

use App\Models\DealerUser;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Bayiye özel private kanal — yalnızca o bayiye ait kullanıcılar dinleyebilir.
 * 'dealer' guard üzerinden yetkilendirilir (DealerUser).
 */
Broadcast::channel('dealer.{dealerId}', function (DealerUser $user, int $dealerId) {
    return (int) $user->dealer_id === $dealerId;
}, ['guards' => ['dealer']]);
