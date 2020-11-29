<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use App\Http\Requests\RegisterRequest;
use App\Http\Controllers\Auth\MenuPoints;

use App\User;
use App\Models\Users\UsersUserRole;

class AuthController extends Controller
{
    
    /**
     * Авторизация пользователя
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function login(Request $request) {

        if (!$request->email)
            return response(['message' => "Введите e-mail, логин или номер телефона"], 400);

        if (!$request->password)
            return response(['message' => "Введите пароль"], 400);

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password]))
            return self::loginDone($request);

        $login = $request->email;
        if (Auth::attempt(['login' => $login, 'password' => $request->password]))
            return self::loginDone($request);

        $phone = $request->email;
        if (Auth::attempt(['phone' => $phone, 'password' => $request->password]))
            return self::loginDone($request);

        return response([
            'message' => "Неверный логин или пароль",
        ], 400);

    }

    /**
     * Завершение авторизации пользователя
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function loginDone(Request $request) {

        $user = Auth::user();

        // Проверка доступа к разделам
        $checkPartAccess = self::checkPartAccess($request->part, $user);

        if (!$checkPartAccess)
            return response(['message' => "Доступ ограничен"], 403);

        $token = $user->createToken('app')->accessToken;

        return response([
            'done' => "success",
            'token' => $token,
            'user' => $user,
        ]);

    }

    /**
     * Выход пользователя, удаление токена
     * 
     * @param Illuminate\Http\Request @request
     * @return response
     */
    public static function logout(Request $request) {

        Auth::user()->token()->revoke();

        return response([
            'done' => 'success',
            'message' => 'Выход произведен',
        ]);

    }

    /**
     * Регистрация нового пользователя
     * 
     * @param Illuminate\Http\Request @request
     * @return response
     */
    public static function registration(RegisterRequest $request) {

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'surname' => $request->surname,
            'name' => $request->name,
            'patronymic' => $request->patronymic,
        ]);

        Auth::attempt(['email' => $request->email, 'password' => $request->password]);

        Auth::user()->assignRole(1);

        $userRole = new UsersUserRole;
        $userRole->user_id = Auth::user()->id;
        $userRole->user_role_id = 1;
        $userRole->save();

        return self::loginDone($request);

    }

    /**
     * Данные пользователя при успешной проверки авторизации
     * 
     * @param Illuminate\Http\Request @request
     * @return response
     */
    public static function user(Request $request) {

        // Проверка доступа к разделам
        $checkPartAccess = self::checkPartAccess($request->part, $request->user());

        if (!$checkPartAccess) {
            return response([
                'message' => "Доступ ограничен",
                'user' => $request->user(),
            ], 403);
        }

        // Формирование пунктов меню
        if ($request->menu == true)
            $menu = MenuPoints::getMenuPoints($request);

        return response([
            'message' => "Доступ разрешен",
            'user' => $request->user(),
            'menu' => $menu ?? null,
        ]);

    }

    /**
     * Метод проверки доступа к разделам сайта
     * 
     * @param string|null $part Тектовый идентификатор раздела
     * @param object $user Объект данных пользователя
     * @return bool
     */
    public static function checkPartAccess($part, $user) {

        if (!$part)
            return true;

        if ($part AND $user->can($part))
            return true;

        return false;

    }

    /**
     * Метод формирования пунктов меню
     * 
     * @param Illuminate\Http\Request @request
     * @return response
     */
    public static function getUserMenu(Request $request) {

        $menu = [];

        // Пункты меню
        $permissions = [
            ['name' => "disk", 'title' => "Диск", 'icon' => "save", 'permission' => "disk"],
            ['name' => "fuel", 'title' => "Расход топлива", 'icon' => "gas-pump", 'permission' => "fuel"],
            ['name' => "users", 'title' => "Пользователи", 'icon' => "users", 'permission' => "admin_users"],
        ];

        // Проверка прав доступа к меню
        foreach ($permissions as $key => $permission) {

            $per = $permission['permission'];
            if ($request->user()->hasPermissionViaRole([$per]) OR $request->user()->hasPermission($per))
                $menu[] = $permissions[$key];
        }

        return response([
            'menu' => $menu,
        ]);

    }

}