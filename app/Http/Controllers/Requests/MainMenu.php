<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Формирование главного меню сайта
|--------------------------------------------------------------------------
*/

class MainMenu extends Controller
{
    
    /**
     * Метод формирвоания бокового меню
     * 
     * @param bool $admin Идентификатор администратора
     * @return array
     */
    public static function getMainMenuPoints($admin = false) {

        $menu = [];

        if ($admin)
            $menu = self::getAdminMenuPoints($admin);

        return $menu;

    }

    /**
     * Пункты меню администратора
     * 
     * @param bool $admin Идентификатор администратора
     * @return array
     */
    public static function getAdminMenuPoints($admin = false) {

        if (!$admin)
            return [];

        // Пункт настроек компании
        $menu[] = [
            [
                'name' => "Настроки компании",
                'icon' => "settings",
                'url' => "/settings",
            ]
        ];

        return $menu;

    }

}
