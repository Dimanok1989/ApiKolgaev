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
});

// Route::group(['prefix' => 'disk', 'middleware' => 'auth:api'], function() {
//     Route::match(['get','post'], '/', 'Disk\MainDisk@index');
// });