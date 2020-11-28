<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\User;

class Profile extends Controller
{
    
    /**
     * Метод соранения данных пользователя
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function saveUserData(Request $request) {

        if (!$user = User::find($request->id))
            return response(['message' => "Данные пользователя не найдены"], 400);

        if ($user->id + 20 != $request->user()->id AND !$request->user()->can('admin.users.edit'))
            return response(['message' => "Доступ к редактированию дргих профилей ограничен"], 403);

        $errors = []; // Ошибки форм

        // Проверка e-mail
        if (!$request->email) {
            $errors['email'] = "Адрес почты обязателен к заполнению";
        }
        else if ($user->email != $request->email) {

            if (!filter_var($email_a, FILTER_VALIDATE_EMAIL))
                $errors['email'] = "Неверный адрес электронной почты";
            else if (User::where('email', $request->email)->count())
                $errors['email'] = "Этот адрес уже используется";
            else
                $user->email = $request->email;

        }

        // Проверка логина
        if ($user->login != $request->login) {

            if (User::where('login', $request->login)->count())
                $errors['login'] = "Этот логин уже используется";
            else
                $user->login = $request->login;

        }

        // Проверка телефона
        if ($user->phone != $request->phone) {

            if (!$request->phone)
                $user->phone = null;
            else {

                $phone = parent::getPhone($request->phone, false);

                if (!$phone)
                    $errors['phone'] = "Телефон указан неверно";
                else if (User::where([['phone', $phone], ['phone', '!=', $user->phone]])->count())
                    $errors['phone'] = "Номер уже используется";
                else
                    $user->phone = $phone;

            }

        }

        // Проверка имени
        if (!$request->name) {
            $errors['name'] = "Обязательно укажите Ваше имя";
        }
        else if ($user->name != $request->name)
            $user->name = $request->name;

        if ($user->surname != $request->surname)
            $user->surname = $request->surname;

        if ($user->patronymic != $request->patronymic)
            $user->patronymic = $request->patronymic;

        if (count($errors)) {
            return response([
                'message' => "Ошибка данных",
                'errors' => $errors,
            ], 400);
        }

        $user->save();

        return response([
            'user' => $user,
        ]);

    }

}
