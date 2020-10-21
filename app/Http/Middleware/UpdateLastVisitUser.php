<?php

namespace App\Http\Middleware;

use Closure;


class UpdateLastVisitUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        if ($request->user()) {
            $user = \App\User::find($request->user()->id);
            $user->last_visit = date("Y-m-d H:i:s");
            $user->save();
        }

        return $next($request);
        
    }
}
