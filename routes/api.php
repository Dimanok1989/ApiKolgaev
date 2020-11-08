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

Route::middleware('auth:api')->match(['get','post'], '/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix' => 'auth'], function () {
    Route::match(['get','post'], '/registration', 'AuthController@registration');
    Route::match(['get','post'], '/login', 'AuthController@login');
    Route::match(['get','post'], '/logout', 'AuthController@logout')->middleware('auth:api');
    Route::match(['get','post'], '/user', 'AuthController@user')->middleware('auth:api');
    Route::match(['get','post'], '/getUserMenu', 'AuthController@getUserMenu')->middleware('auth:api');
});

Route::group([
    'prefix' => 'disk',
    'middleware' => [
        'auth:api',
        'permission:disk'
    ],
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
});

// Route::post('/disk/uploadFile', 'Disk\UploadFile@upload')->middleware('auth:api');

/**
 * Раздел расхода топлива
 */
Route::group([
    'prefix' => 'fuel',
    'middleware' => [
        'auth:api',
        'permission:fuel'
    ],
], function() {

    Route::post('/getMainData', 'Fuel\Fuel@getMainData');
    Route::post('/getFuelsCar', 'Fuel\Fuel@getFuelsCar');
    
    Route::post('/addFuel', 'Fuel\Fuel@addFuel');

});

/**
 * Раздел расхода топлива
 */
Route::group([
    'prefix' => 'admin',
    'middleware' => [
        'auth:api',
        'permission:admin'
    ],
], function() {

    Route::group([
        'prefix' => 'users',
        'middleware' => 'permission:admin_users'
    ], function() {

        Route::post('/getRoles', 'Admin\Users@getRoles');
        Route::post('/getPermissions', 'Admin\Users@getPermissions');
        Route::post('/setPermissionRole', 'Admin\Users@setPermissionRole');

        Route::post('/getLastUsers', 'Admin\Users@getLastUsers');
        Route::post('/getUserData', 'Admin\Users@getUserData');

        Route::post('/setRole', 'Admin\Users@setRole');
        Route::post('/setPermissionUser', 'Admin\Users@setPermissionUser');

    });

});