<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Controllers\Requests\MainMenu;

class Requests extends Controller
{

    /**
     * Метод первоначальной загрузки данных пользователя
     * 
     * @param Illuminate\Http\Request
     * @return response
     */
    public static function checkUser(Request $request) {

        // Доступ к разделу
        $access = $request->user()->can('requests.access');

        // Доступ администратора
        $admin = $request->user()->can('requests.admin');

        // Пункты главного меню
        $menu = $access ? MainMenu::getMainMenuPoints($admin) : [];

        return response()->json([
            'user' => $request->user(),
            'access' => $access,
            'admin' => $admin,
            'menu' => $menu
        ]);

    }

    /**
     * Метод загрузки данных для формирования главной страницы
     * 
     * @param Illuminate\Http\Request
     * @return response
     */
    public static function load(Request $request) {

        // Доступ к разделу
        $access = $request->user()->can('requests.access');

        // Доступ администратора
        $admin = $request->user()->can('requests.admin');

        // Пункты главного меню
        $menu = $access ? MainMenu::getMainMenuPoints($admin) : [];

        return response()->json([
            'access' => $access,
            'admin' => $admin,
            'menu' => $menu
        ]);

    }

}
