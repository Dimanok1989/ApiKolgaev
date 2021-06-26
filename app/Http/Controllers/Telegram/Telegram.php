<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Telegram\Bot\Api;

class Telegram extends Controller
{
    
    public static function webhook(Request $request) {
        
        // // dd(env('TELEGRAM_CERTIFICATE_PATH'));

        // $client = new \GuzzleHttp\Client(['defaults' => [
        //     'verify' => 'false'
        // ]]);
        $telegram = new Api();

        // $updates = $telegram->getWebhookUpdates();
        $response = $telegram->getMe();

        // $botId = $response->getId();
        // $firstName = $response->getFirstName();
        // $username = $response->getUsername();

        return response()->json([
            $response
        ]);

    }

}
