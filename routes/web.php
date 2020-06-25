<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('spa');
    return view('welcome');
});

Route::get('/dev', function () {
    $user = App\User::find(1);
    // dd($user->isAdmin);
    // dd($user->hasRole('web-developer')); // вернёт true
    // dd($user->hasRole('project-manager'));// вернёт false
    // dd($user->givePermissionsTo('manage-users'));
    dd($user->hasPermissionTo('manage-users'));// вернёт true
});

Route::get('/{any}', function () {
    return view('spa');
});

// Auth::routes();