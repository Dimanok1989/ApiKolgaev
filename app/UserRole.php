<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\UserPermission;

class UserRole extends Model
{
    
    public function permissions() {

        return $this->belongsToMany(UserPermission::class, 'user_roles_user_permissions');

    }

}