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

            // Проверка и обновление токена для прямых ссылок
            if ($request->user()->email_verified_at < date("Y-m-d 23:59:59")) {
                $request->user()->email_verified_at = date("Y-m-d 23:59:59");
                $request->user()->remember_token = md5($request->user()->email . $request->user()->id . time());
            }

            $request->user()->save();

        }

        return $next($request);
        
    }
}
