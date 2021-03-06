<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Requests extends Controller
{

    /**
     * Метод первоначальной загрузки данных пользователя
     * 
     * @param Illuminate\Http\Request
     * @return response
     */
    public static function load(Request $request) {

        return response()->json();

    }
}
