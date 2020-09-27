<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Users\UserRoles;
use App\Models\Users\UserPermissions;
use App\Models\Users\UserRolesUserPermission;
use App\Models\Users\UsersUserRole;

class Users extends Controller
{
    
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
            '$count' => $count,
        ]);

    }

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

    public static function setPermissionRole(Request $request) {

        // Проверка наличия записи
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

}
