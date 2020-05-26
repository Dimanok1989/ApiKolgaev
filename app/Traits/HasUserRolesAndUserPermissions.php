<?php

namespace App\Traits;

use App\UserRole;
use App\UserPermission;

trait HasUserRolesAndUserPermissions
{

    /**
     * @return mixed
     */
    public function roles() {

        return $this->belongsToMany(UserRole::class, 'users_user_roles');

    }

    /**
     * @return mixed
     */
    public function permissions() {

        return $this->belongsToMany(UserPermission::class, 'users_user_permissions');

    }

    /**
     * Метод проверки ролей пользователя
     * 
     * @param mixed ...$roles
     * @return bool
     */
    public function hasRole(... $roles) {

        foreach ($roles as $role) {
            if ($this->roles->contains('slug', $role)) {
                return true;
            }
        }

        return false;

    }

    /**
     * Метод проверяет, содержат ли права пользователя заданное право, если да, то тогда он вернет true, а иначе false.
     * 
     * @param $permission
     * @return bool
     */
    public function hasPermission($permission) {

        return (bool) $this->permissions->where('slug', $permission)->count();

    }

    /**
     * Метод проверяет, содержат ли права пользователя заданное право, если да, то тогда он вернет true, а иначе false.
     * 
     * @param $permission
     * @return bool
     */
    public function hasPermissionTo($permission) {

        return $this->hasPermissionThroughRole($permission) || $this->hasPermission($permission);

    }

    /**
     * Эта функция проверяет, привязана ли Роль с Правами к Пользователю. Метод hasPermissionTo() проверит эти два условия.
     * 
     * @param $permission
     * @return bool
     */
    public function hasPermissionThroughRole($permission) {

        foreach ($permission->roles as $role){
            if ($this->roles->contains($role)) {
                return true;
            }
        }

        return false;

    }

    /**
     * Метод получает все Права на основе переданного массива
     * 
     * @param array $permissions
     * @return mixed
     */
    public function getAllPermissions(array $permissions) {

        return UserPermission::whereIn('slug', $permissions)->get();

    }

    /**
     * @param mixed ...$permissions
     * @return $this
     */
    public function givePermissionsTo(... $permissions) {

        $permissions = $this->getAllPermissions($permissions);
        if ($permissions === null) {
            return $this;
        }

        $this->permissions()->saveMany($permissions);
        return $this;

    }

    /**
     * @param mixed ...$permissions
     * @return $this
     */
    public function deletePermissions(... $permissions ) {

        $permissions = $this->getAllPermissions($permissions);
        $this->permissions()->detach($permissions);
        return $this;
        
    }

    /**
     * @param mixed ...$permissions
     * @return HasRolesAndPermissions
     */
    public function refreshPermissions(... $permissions ) {

        $this->permissions()->detach();
        return $this->givePermissionsTo($permissions);

    }


}