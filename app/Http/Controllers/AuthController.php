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

        if (Auth::attempt($request->only('email', 'password')))
            return self::loginDone();

        $login = $request->email;
        if (Auth::attempt(['login' => $login, 'password' => $request->password]))
            return self::loginDone();

        $phone = $request->email;
        if (Auth::attempt(['phone' => $phone, 'password' => $request->password]))
            return self::loginDone();

        return response([
            'message' => "Неправильный логин или пароль",
        ], 401);

    }

    public static function loginDone() {

        $user = Auth::user();
        $token = $user->createToken('app')->accessToken;

        return response([
            'status' => "success",
            'token' => $token,
            'user' => $user,
        ]);

    }

    public static function register(RegisterRequest $request) {

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'surname' => $request->surname,
            'name' => $request->name,
            'patronymic' => $request->patronymic,
        ]);

        return $user;

    }

    public static function user(Request $request) {

        return Auth::user();

    }

}