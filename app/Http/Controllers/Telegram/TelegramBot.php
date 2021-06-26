<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Telegram\Bot\Api as TelegramApi;
use App\Models\TelegramIncomingMessage;

class TelegramBot extends Controller
{
    
    /**
     * Обработка входящего запроса
     * 
     * @param Illuminate\Http\Request
     * @return response
     */
    public static function webhook(Request $request) {

        $telegram = new TelegramApi();
        $updates = $telegram->getWebhookUpdates();

        $incoming = TelegramIncomingMessage::create([
            'message_id' => $updates['message']['message_id'] ?? null,
            'chat_id' =>  $updates['message']['chat']['id'] ?? null,
            'message' =>  $updates['message']['text'] ?? null,
            'request' => \json_encode($updates, JSON_UNESCAPED_UNICODE),
        ]);

        return response()->json([
            'id' => $incoming->id,
        ]);

    }

}
