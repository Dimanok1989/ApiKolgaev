<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/** Авторизация на канале широковещания */
Route::middleware('auth:api')
->match(['get','post'], '/broadcasting/auth', function (Request $request) {
    return \Illuminate\Support\Facades\Broadcast::auth($request);
});

Route::group(['prefix' => 'auth'], function () {
    /** Регистрация нового пользователя */
    Route::post('/registration', 'Auth\AuthController@registration');
    /** Авторизация пользователя */
    Route::post('/login', 'Auth\AuthController@login');
    /** Деавторизация пользователя */
    Route::post('/logout', 'Auth\AuthController@logout')->middleware('auth:api');
    /** Данные пользователя */
    Route::post('/user', 'Auth\AuthController@user')->middleware('auth:api');
    /** Пункты меню пользователя */
    Route::post('/getMenu', 'Auth\AuthController@getMenu')->middleware('auth:api');
    /** Список пунктов меню пользователя */
    Route::post('/getUserMenu', 'Auth\AuthController@getUserMenu')->middleware('auth:api');
    /** Изменение данных пользователя из личного кабинета */
    Route::post('/saveUserData', 'Auth\Profile@saveUserData')->middleware('auth:api');
});

Route::group([
    'prefix' => 'disk',
    'middleware' => ['auth:api', 'permission:disk']
], function() {
    Route::post('/getUsersList', 'Disk\MainDataDisk@getUsersList');
    Route::post('/getUserFiles', 'Disk\MainDataDisk@getUserFiles');
    Route::post('/uploadFile', 'Disk\UploadFile@upload');
    Route::post('/mkdir', 'Disk\MainDataDisk@mkdir');
    Route::post('/getNameFile', 'Disk\MainDataDisk@getNameFile');
    Route::post('/rename', 'Disk\MainDataDisk@rename');
    Route::post('/startDownload', 'Disk\DownloadFile@startDownload');
    Route::post('/download', 'Disk\DownloadFile@download');
    Route::post('/addFileToZip', 'Disk\DownloadFile@addFileToZip');
    Route::post('/deleteFile', 'Disk\MainDataDisk@deleteFile');
    Route::post('/showImage', 'Disk\MainDataDisk@showImage');
});

// Route::get('/ttttttt', function() {
//     echo \Crypt::encryptString('123');
//     \App\Events\DiskOnline::dispatch([123, 456]);
// });

// Route::post('/disk/uploadFile', 'Disk\UploadFile@upload')->middleware('auth:api');

/**
 * Раздел расхода топлива
 */
Route::group([
    'prefix' => 'fuel',
    'middleware' => ['auth:api', 'permission:fuel'],
], function() {

    /** Добавление новой машины */
    Route::post('/addNewCar', 'Fuel\Fuel@addNewCar');

    Route::post('/getMainData', 'Fuel\Fuel@getMainData');
    Route::post('/getFuelsCar', 'Fuel\Fuel@getFuelsCar');
    
    Route::post('/showAddFuel', 'Fuel\Fuel@showAddFuel');
    Route::post('/addFuel', 'Fuel\Fuel@addFuel');

});

/** Раздел админ панели */
Route::group([
    'prefix' => 'admin',
    'middleware' => [
        'auth:api',
        'permission:admin'
    ],
], function() {

    /** Управление пользователями, ролями и правами */
    Route::group([
        'prefix' => 'users',
        'middleware' => 'permission:admin.users'
    ], function() {
        /** Список всех ролей */
        Route::post('/getRoles', 'Admin\Users@getRoles');
        /** Данные одной роли */
        Route::post('/getRoleData', 'Admin\Users@getRoleData');
        /** Выдать права роли */
        Route::post('/setPermissionToRole', 'Admin\Users@setPermissionToRole');
        /** Список всех прав */
        Route::post('/getPermissions', 'Admin\Users@getPermissions');
        /** Создание права */
        Route::post('/createPermission', 'Admin\Users@createPermission');
        /** Поиск пользователей */
        Route::post('/getUsers', 'Admin\Users@getUsers');
        /** Данные пользователя */
        Route::post('/getUserData', 'Admin\Users@getUserData');
        /** Установка роли пользователю */
        Route::post('/setRoleToUser', 'Admin\Users@setRoleToUser');
        /** Установка права пльзователю */
        Route::post('/setPermissionToUser', 'Admin\Users@setPermissionToUser');
        
        // Route::post('/setPermissionRole', 'Admin\Users@setPermissionRole');
        // Route::post('/getLastUsers', 'Admin\Users@getLastUsers');
        // Route::post('/setRole', 'Admin\Users@setRole');
        // Route::post('/setPermissionUser', 'Admin\Users@setPermissionUser');

    });

});