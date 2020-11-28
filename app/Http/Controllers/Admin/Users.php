<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Users\RoleHasPermission;
use App\Models\Users\ModelHasPermission;
use App\Models\Users\ModelHasRole;

use App\Models\Users\UserRoles;
use App\Models\Users\UserPermissions;
use App\Models\Users\UserRolesUserPermission;
use App\Models\Users\UsersUserRole;
use App\Models\Users\UsersUserPermission;

class Users extends Controller
{

    /**
     * Метод формирования массива охранников
     * 
     * @return array
     */
    public static function getGuards() {

        foreach (config('auth.guards') as $guard => $array) {
            $guards[] = ['guard_name' => $guard];
        }

        return $guards ?? [];

    }
    
    /**
     * Получение списка ролей пользователей
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function getRoles(Request $request) {

        $roles = Role::orderBy('guard_name')->orderBy('name')->get();

        // Количество пользователей
        $counts = ModelHasRole::select(\DB::raw('COUNT(*) as count, role_id'))
        ->groupBy('role_id')->get();

        foreach ($counts as $count) {
            $users[$count->role_id] = $count->count;
        }

        // Количество прав
        $counts = RoleHasPermission::select(\DB::raw('COUNT(*) as count, role_id'))
        ->groupBy('role_id')->get();

        foreach ($counts as $count) {
            $rights[$count->role_id] = $count->count;
        }

        foreach ($roles as &$role) {

            $role->created = parent::createDate($role->created_at);
            $role->updated = parent::createDate($role->updated_at);

            $role->users = $users[$role->id] ?? 0;
            $role->rights = $rights[$role->id] ?? 0;

        }

        return response([
            'roles' => $roles,
            'guards' => self::getGuards() ?? ['guard_name' => "api"],
        ]);

    }

    /**
     * Метод вывод данных роли
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function getRoleData(Request $request) {

        if (!$role = Role::find($request->id))
            return response(['message' => "Роль не найдена"], 400);

        // Все права
        $permissions = Permission::where('guard_name', $role->guard_name)->orderBy('name')->get();

        // Отмеченные права
        $checked = [];
        foreach (RoleHasPermission::where('role_id', $role->id)->get() as $row) {
            $checked[] = $row->permission_id;
        }

        foreach ($permissions as &$permission) {
            $permission->checked = in_array($permission->id, $checked);
        }

        return response([
            'name' => $role->name,
            'permissions' => $permissions ?? [],
        ]);

    }

    /**
     * Установка прав роли
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function setPermissionToRole(Request $request) {

        if (!$role = Role::find($request->role))
            return response(['message' => "Роли не существует"], 400);

        if (!$permission = Permission::find($request->permission))
            return response(['message' => "Право не существует"], 400);

        if ($request->checked === null)
            return response(['message' => "Чекбокс не определен"], 400);

        if ($request->checked === true)
            $set = $role->givePermissionTo($permission->name);
        elseif ($request->checked === false)
            $set = $role->revokePermissionTo($permission->name);

        return response([
            'checked' => $request->checked,
        ]);

    }

    /**
     * Получение списка прав
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function getPermissions(Request $request) {

        $permissions = Permission::orderBy('guard_name')->orderBy('name')->get();

        // Количество пользователей, имеющих право
        $counts = ModelHasPermission::select(\DB::raw('COUNT(*) as count, permission_id'))
        ->groupBy('permission_id')->get();

        foreach ($counts as $count) {
            $users[$count->permission_id] = $count->count;
        }

        // Количество ролей, имеющих право
        $counts = RoleHasPermission::select(\DB::raw('COUNT(*) as count, permission_id'))
        ->groupBy('permission_id')->get();

        foreach ($counts as $count) {
            $rights[$count->permission_id] = $count->count;
        }

        foreach ($permissions as &$permit) {

            $permit->created = parent::createDate($permit->created_at);
            $permit->updated = parent::createDate($permit->updated_at);

            $permit->users = $users[$permit->id] ?? 0;
            $permit->rights = $rights[$permit->id] ?? 0;

        }

        return response([
            'permissions' => $permissions ?? [],
            'guards' => self::getGuards() ?? ['guard_name' => "api"],
        ]);

    }

    /**
     * Создание нового права
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function createPermission(Request $request) {

        if (!$request->name)
            return response(['message' => "Не указано значение права"], 400);

        if (!$request->guard_name)
            return response(['message' => "Не выбран оххранник права"], 400);

        $count = Permission::where([
            ['name', $request->name],
            ['guard_name', $request->guard_name],
        ])->count();

        if ($count)
            return response(['message' => "Такое сочетание права и охранника уже создано"], 400);

        $permission = Permission::create([
            'name' => $request->name,
            'guard_name' => $request->guard_name,
        ]);

        $permission->created = parent::createDate($permission->created_at);
        $permission->updated = parent::createDate($permission->updated_at);
        $permission->users = 0;
        $permission->rights = 0;

        return response([
            'permission' => $permission,
        ]);

    }

    /**
     * Поиск пользователей
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function getUsers(Request $request) {

        $phone = parent::getPhone($request->search, false);

        $users = User::where(function($query) use ($request, $phone) {

            $query->where('email', 'LIKE', "%{$request->search}%")
                ->orWhere('login', 'LIKE', "%{$request->search}%")
                ->orWhere('surname', 'LIKE', "%{$request->search}%")
                ->orWhere('name', 'LIKE', "%{$request->search}%")
                ->orWhere('patronymic', 'LIKE', "%{$request->search}%");

            if ($phone)
                $query->orWhere('phone', 'LIKE', "%{$phone}%");

        });

        if (!$request->search)
            $users = $users->orderBy('id', 'DESC')->limit(10);

        $users = $users->get();

        foreach ($users as &$user) {
            
            $user->visit = $user->last_visit ? parent::createDate($user->last_visit) : null;
            $user->roles = $user->getRoleNames();

        }

        return response([
            'users' => $users ?? [],
        ]);

    }

    /**
     * Метод вывода данных по одному пользователю
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function getUserdata(Request $request) {

        if (!$user = User::find($request->id))
            return response(['message' => "Пользователь не найден"], 400);

        $user->visit = $user->last_visit ? parent::createDate($user->last_visit) : null;

        // Права пользователя
        foreach ($user->permissions as $permit) {
            $userPermissions[] = [
                'id' => $permit->id,
                'guard_name' => $permit->guard_name,
                'name' => $permit->name,
            ];
        }

        // Все права
        $permissions = Permission::orderBy('guard_name')->orderBy('name')->get();

        // Роли пользователя
        foreach ($user->roles as $role) {
            $userRoles[] = [
                'id' => $role->id,
                'guard_name' => $role->guard_name,
                'name' => $role->name,
            ];
        }

        // Все права
        $roles = Role::orderBy('guard_name')->orderBy('name')->get();

        return response([
            'user' => $user,
            'userPermissions' => $userPermissions ?? [],
            'permissions' => $permissions,
            'userRoles' => $userRoles ?? [],
            'roles' => $roles,
        ]);

    }

    /**
     * Установка роли пользователю
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function setRoleToUser(Request $request) {

        if (!$user = User::find($request->user))
            return response(['message' => "Пользователь не найден"], 400);

        if ($request->checked == true) {
            $user->assignRole($request->role);
            $message = "Роль выдана пользователю";
        }
        else {
            $user->removeRole($request->role);
            $message = "Роль отозвана у пользователя";
        }

        return response([
            'message' => $message ?? "Настройка применена",
            'checked' =>  $request->checked,
        ]);

    }

    /**
     * Установка права пльзователю
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function setPermissionToUser(Request $request) {

        if (!$user = User::find($request->user))
            return response(['message' => "Пользователь не найден"], 400);

        if ($request->checked == true) {
            $user->givePermissionTo($request->permission);
            $message = "Право выдано пользователю";
        }
        else {
            $user->revokePermissionTo($request->permission);
            $message = "Право отозвано у пользователя";
        }

        return response([
            'message' => $message ?? "Настройка применена",
            'checked' =>  $request->checked,
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
    public static function getUserDataOld(Request $request) {

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
