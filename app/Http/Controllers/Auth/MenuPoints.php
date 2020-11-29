<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Users\RoleHasPermission;
use App\Models\Users\ModelHasPermission;
use App\Models\Users\ModelHasRole;

class MenuPoints extends Controller
{
 
    /**
     * Выборка пунктов меню для пользователя
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function getMenuPoints(Request $request) {

        $menu = [];

        foreach ($request->user()->getAllPermissions() as $permit) {

            if (strripos($permit->name, "menu.") !== false) {
                $menu[] = $permit->name;
            }

        }

        return $menu;

    }

}
