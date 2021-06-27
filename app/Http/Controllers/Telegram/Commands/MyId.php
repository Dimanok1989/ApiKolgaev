<?php

namespace App\Http\Controllers\Telegram\Commands;

use Telegram\Bot\Actions;
use Telegram\Bot\Commands\Command;

class MyId extends Command
{

    /**
     * @var string Command Name
     */
    protected $name = "myid";

    /**
     * @var string Command Description
     */
    protected $description = "Узнать свой идентификатор";

    /**
     * {@inheritdoc}
     */
    public function handle() {

        $text = '';

        $update = $this->getUpdate();
        $chat_id = $update['message']['chat']['id'] ?? null;

        $text .= $chat_id ? "*{$chat_id}*" : "_Не определен_";

        $this->replyWithMessage([
            'text' => $text,
            'parse_mode' => "MarkdownV2",
        ]);

    }

}