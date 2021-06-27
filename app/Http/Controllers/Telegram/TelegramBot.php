<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Telegram\Bot\Api as TelegramApi;
use App\Models\TelegramIncomingMessage;

class TelegramBot extends Controller
{

    /**
     * Входящие данные
     * 
     * @var object
     */
    public $request;

    /**
     * Объект телеграм бота
     * 
     * @var object
     */
    protected $telegram;

    /**
     * Создание объекта
     */
    public function __construct(Request $request) {

        $this->request = $request;

        $this->telegram = new TelegramApi();

        $this->addCommands();
        
    }

    /**
     * Регистрация команд для обработки
     */
    public function addCommands() {

        $this->telegram->addCommands([
            Commands\Start::class,
            Commands\Help::class,
            Commands\MyId::class,
        ]);

    }
    
    /**
     * Обработка входящего запроса
     * 
     * @return response
     */
    public function webhook() {

        $updates = $this->telegram->commandsHandler(true);

        $incoming = TelegramIncomingMessage::create([
            'message_id' => $updates['message']['message_id'] ?? null,
            'chat_id' =>  $updates['message']['chat']['id'] ?? null,
            'message' =>  $updates['message']['text'] ?? null,
            'request' => \json_encode($updates ?? [], JSON_UNESCAPED_UNICODE),
        ]);

        return response()->json([
            'id' => $incoming->id,
        ]);

    }

}
