<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\User;
use App\Models\Users\UserRoles;
use App\Models\Users\UserPermissions;
use App\Models\Users\UserRolesUserPermission;
use App\Models\Users\UsersUserRole;
use App\Models\Users\UsersUserPermission;

class Users extends Controller
{
    
    /**
     * Получение списка групп пользователей
     */
    public static function getRoles(Request $request) {

        $roles = UserRoles::orderBy('name')->get();

        $count = UsersUserRole::select(
            \DB::raw('COUNT(*) as count, user_role_id')
        )
        ->groupBy('user_role_id')->get();

        $counts = [];
        foreach ($count as $row)
            $counts[$row->user_role_id] = $row->count;

        foreach ($roles as &$role)
            $role->count = $counts[$role->id] ?? 0;

        return response([
            'roles' => $roles,
        ]);

    }

    /**
     * Получение списка прав
     */
    public static function getPermissions(Request $request) {

        $permissions = UserPermissions::orderBy('slug')->orderBy('name')->get();
        $access = UserRolesUserPermission::where('user_role_id', $request->id)->get();

        $permission_id = [];
        foreach ($access as $row)
            $permission_id[] = $row->user_permission_id;

        foreach ($permissions as &$row)
            $row->access = in_array($row->id, $permission_id);

        return response([
            'permissions' => $permissions,
        ]);

    }

    /**
     * Установка права для группы
     */
    public static function setPermissionRole(Request $request) {

        $set = new UserRolesUserPermission;

        if (!$request->checked) {
            $set->where([
                ['user_role_id', $request->role],
                ['user_permission_id', $request->id],
            ])->delete();
        }
        else {
            $set->user_role_id = $request->role;
            $set->user_permission_id = $request->id;
            $set->save();
        }

        return response([
            'set' => $set
        ]);

    }

    /**
     * Получение списка последних зарегистрированных пользователей
     */
    public static function getLastUsers(Request $request) {

        $rows = User::select('users.*', 'user_roles.name as role', 'user_roles.id as role_id')
        ->leftjoin('users_user_roles', 'users.id', '=', 'users_user_roles.user_id')
        ->leftjoin('user_roles', 'user_roles.id', '=', 'users_user_roles.user_role_id')
        ->orderBy('users.id', 'DESC')
        ->limit(10)
        ->get();

        foreach ($rows as $row)
            $users[] = self::getRowOneUser($row);

        $count = User::count();

        return response([
            'users' => $users ?? [],
            'count' => $count,
        ]);

    }

    /**
     * Обработка строки данных пользователя
     */
    public static function getRowOneUser($row) {

        $date = date("d.m.Y H:i", strtotime($row->created_at));

        $name = $row->surname;
        $name .= " " . $row->name;
        $name .= " " . $row->patronymic;

        return [
            'id' => $row->id,
            'email' => $row->email,
            'login' => $row->login,
            'phone' => $row->phone,
            'name' => trim($name),
            'role' => $row->role,
            'role_id' => $row->role_id ?? 0,
            'date' => $date,
            'last' => $row->last_visit ? date("d.m.Y H:i:s", strtotime($row->last_visit)) : null,
        ];

    }

    /**
     * Получение данных пользователя для установки индивидуальных прав
     */
    public static function getUserData(Request $request) {

        // Проверка пользователя
        if (!$user = User::find($request->id))
            return response(['message' => "Пользователь не найден"], 400);

        // Группа пользователя
        $user_role = UsersUserRole::where('user_id', $user->id)->get();
        $user_role_id = count($user_role) ? $user_role[0]->user_role_id : false;

        // Список групп
        $roles = UserRoles::orderBy('name')->get();

        // Поиск группы пользователя
        foreach ($roles as $row) {
            if ($row->id == $user_role_id) {
                $user->role = $row->name;
                $user->role_id = $row->id;
            }
        }

        // Список всех прав
        $permissions = UserPermissions::orderBy('slug')->orderBy('name')->get();

        // Список индивидуальных прав
        $access = UsersUserPermission::where('user_id', $user->id)->get();

        $user_permission = []; // Массив идентификаторов индивидуальных прав
        foreach ($access as $row)
            $user_permission[] = $row->user_permission_id;

        // Список прав группы
        $access = UserRolesUserPermission::where('user_role_id', $user->role_id)->get();

        $role_permissions = [];
        foreach ($access as $row)
            $role_permissions[] = $row->user_permission_id;

        // Добавление флага включенных индивидуальных прав
        foreach ($permissions as &$row) {
            $row->on = in_array($row->id, $user_permission);
            $row->access = in_array($row->id, $user_permission) ? 1 : 0;
            $row->role_on = in_array($row->id, $role_permissions);
        }

        // Вывод данных
        return response([
            'user' => self::getRowOneUser($user),
            'permissions' => $permissions,
            'roles' => $roles,
        ]);

    }

    /**
     * Смена группы пользователю
     */
    public static function setRole(Request $request) {

        // Проверка пользователя
        if (!$user = User::find($request->id))
            return response(['message' => "Пользователь не найден"], 400);

        // Удаление старых значений
        UsersUserRole::where('user_id', $request->id)->delete();

        $role = (int) $request->role;

        // Если группа удаляется
        if (!$role)
            return response([]);

        // Проверка наличия группы
        if (!UserRoles::find($role))
            return response(['message' => "Группа не найдена"], 400);

        $create = new UsersUserRole;
        $create->user_id = $request->id;
        $create->user_role_id = $role;
        $create->save();

        return response([
            'create' => $create,
        ]);

    }

    /**
     * Установка права для пользователя
     */
    public static function setPermissionUser(Request $request) {

        $set = new UsersUserPermission;

        if (!$request->checked) {
            $set->where([
                ['user_id', $request->user],
                ['user_permission_id', $request->id],
            ])->delete();
        }
        else {
            $set->user_id = $request->user;
            $set->user_permission_id = $request->id;
            $set->save();
        }

        return response([
            'set' => $set
        ]);

    }

}
