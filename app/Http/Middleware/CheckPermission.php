<?php

namespace App\Http\Middleware;

use Closure;
use App\UserPermission;
use App\UserRole;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, ... $permissions)
    {

        if ($request->user()->hasPermissionViaRole($permissions) OR $request->user()->hasPermission($permissions))
            return $next($request);

        return response([
            'message' => "Доступ ограничен",
        ], 403);

    }
}
