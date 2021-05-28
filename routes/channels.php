<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

/** Канал присутствия Диска */
Broadcast::channel('App.Disk', function ($user) {

    if ($user->can('disk')) {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'surname' => $user->surname,
            'patronymic' => $user->patronymic,
            'login' => $user->login,
        ];
    }

    return false;

});

/** Канал присутствия Диска */
Broadcast::channel('App.Disk.Chat', function ($user) {

    if ($user->can('disk')) {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'surname' => $user->surname,
            'patronymic' => $user->patronymic,
            'login' => $user->login,
        ];
    }

    return false;

});

/** Канал присутствия Основного сайта */
Broadcast::channel('App.Main', function ($user) {

    return [
        'id' => $user->id,
        'email' => $user->email,
        'name' => $user->name,
        'surname' => $user->surname,
        'patronymic' => $user->patronymic,
        'login' => $user->login,
    ];

});

// Broadcast::channel('App.User.{id}', function ($user, $id) {
//     return (int) $user->id === (int) $id;
// });
