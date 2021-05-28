<?php

namespace App\Http\Controllers\Disk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Disk\ChatMessage;

class Chats extends Controller
{
    
    /**
     * Отправка сообщения в чат
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function sendMessage(Request $request) {

        $message = ChatMessage::create([
            'user_id' => $request->user()->id,
            'message' => $request->message,
            'created_at' => date("Y-m-d H:i:s", round($request->microtime / 1000)),
        ]);

        $message->_name = $request->user()->name;
        $message->_surname = $request->user()->surname;

        $message = self::getMessageRow($message);

        broadcast(new \App\Events\DiskChat([
            'new' => $message,
        ]))->toOthers();

        return response()->json([
            'message' => $message,
        ]);

    }

    /**
     * Подготовка данных для вывод строки сообщения
     * 
     * @param \App\Models\Disk\ChatMessage $message
     * @return object $message
     */
    public static function getMessageRow($message) {

        $message->name = $message->_name;
        $message->name .= $message->_surname ? " " . $message->_surname : "";

        $message->status = "sent";

        if ($message->created_at >= date("Y-m-d 00:00:00") AND $message->created_at <= date("Y-m-d 23:59:59"))
            $message->date = date("H:i", strtotime($message->created_at));
        else
            $message->date = date("d.m.Y H:i", strtotime($message->created_at));

        return $message;

    }

    /**
     * Вывод сообщений для чата
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function getMessages(Request $request) {

        $data = ChatMessage::select(
            'chat_messages.*',
            'users.name as _name',
            'users.surname as _surname',
        )
        ->leftjoin('users', 'users.id', '=', 'chat_messages.user_id')
        ->orderBy('id', 'DESC')
        ->paginate(30);

        $messages = [];

        foreach ($data as $row) {
            $messages[] = self::getMessageRow($row);
        }

        return response()->json([
            'messages' => $messages ?? [],
            'nextPage' => $data->currentPage() + 1,
            'lastPage' => $data->lastPage(),
        ]);

    }

}
