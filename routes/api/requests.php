<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Маршрутизаяция для раздела менеджера заявок
|--------------------------------------------------------------------------
*/

Route::group([
    'middleware' => ['auth:api', 'permission:requests']
], function() {

    /** Первоначальная проверка пользователя */
    Route::post('/checkUser', 'Requests\Requests@checkUser');
    /** Загрузка данных */
    Route::post('/load', 'Requests\Requests@load');

});
