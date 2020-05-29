<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use App\Traits\HasUserRolesAndUserPermissions;
use DB;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasUserRolesAndUserPermissions;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'login', 'phone', 'email', 'password', 'surname', 'name', 'patronymic'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public static function getUsersListForDisk() {
        
        return DB::table('users')
            ->select('users.*')
            ->leftjoin('users_user_roles', 'users_user_roles.user_id', '=', 'users.id')
            ->leftjoin('user_roles_user_permissions', 'user_roles_user_permissions.user_role_id', '=', 'users_user_roles.user_role_id')
            ->leftjoin('users_user_permissions', 'users_user_permissions.user_id', '=', 'users.id')
            ->where('user_roles_user_permissions.user_permission_id', '1')
            ->orWhere('users_user_permissions.user_permission_id', '1')
            ->distinct()
            ->get();

    }

}
