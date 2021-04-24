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

            $date = date("Y-m-d H:i:s");

            // Обновление времени последнего действия пользователя
            $request->user()->last_visit = $date;
            $request->user()->save();

        }

        return $next($request);
        
    }
}
