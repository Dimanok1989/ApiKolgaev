<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use App\Http\Requests\RegisterRequest;

use App\User;

class AuthController extends Controller
{
    
    public static function login(Request $request) {

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password]))
            return self::loginDone();

        $login = $request->email;
        if (Auth::attempt(['login' => $login, 'password' => $request->password]))
            return self::loginDone();

        $phone = $request->email;
        if (Auth::attempt(['phone' => $phone, 'password' => $request->password]))
            return self::loginDone();

        return response([
            'done' => "error",
            'message' => "Неверный логин или пароль",
        ]);

    }

    public static function loginDone() {

        $user = Auth::user();
        $token = $user->createToken('app')->accessToken;

        return response([
            'done' => "success",
            'token' => $token,
            'user' => $user,
        ]);

    }

    public static function logout(Request $request) {

        Auth::user()->token()->revoke();

        return response([
            'done' => 'success',
            'message' => 'Выход произведен',
        ]);

    }

    public static function registration(RegisterRequest $request) {

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'surname' => $request->surname,
            'name' => $request->name,
            'patronymic' => $request->patronymic,
        ]);

        Auth::attempt(['email' => $request->email, 'password' => $request->password]);
        return self::loginDone();

    }

    public static function user(Request $request) {

        return Auth::user();

    }

    /**
     * Метод формирования пунктов меню
     */
    public static function getUserMenu(Request $request) {

        $menu = [];

        // Пункты меню
        $permissions = [
            ['name' => "disk", 'title' => "Диск", 'icon' => "save"],
            ['name' => "fuel", 'title' => "Расход топлива", 'icon' => "gas-pump"],
        ];

        // Проверка прав доступа к меню
        foreach ($permissions as $key => $permission)
            if ($request->user()->hasPermissionViaRole([$permission['name']]))
                $menu[] = $permissions[$key];

        return response([
            'menu' => $menu,
        ]);

    }

}