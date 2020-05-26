<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public static function done($data = [], $message = null) {

        return response([
            'status' => "success",
            'message' => $message,
            'body' => $data,
        ], 200);

    }

    public static function error($message = null, $errors = [], $code = 400) {

        return response([
            'status' => "error",
            'message' => $message,
            'errors' => $errors
        ], $code);

    }

}
