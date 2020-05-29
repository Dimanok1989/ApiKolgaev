<?php

namespace App\Http\Middleware;

use Closure;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, ... $roles)
    {

        if ($request->user()->hasRoles($roles))
            return $next($request);

        return response([
            'message' => "Доступ ограничен",
        ], 403);

    }
    
}
