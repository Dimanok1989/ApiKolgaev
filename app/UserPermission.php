<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\UserRole;

class UserPermission extends Model
{
    
    public function roles()
    {
        return $this->belongsToMany(UserRole::class, 'user_roles_user_permissions');
    }

}