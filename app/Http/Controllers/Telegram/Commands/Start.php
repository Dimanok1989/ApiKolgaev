<?php

namespace App\Http\Controllers\Telegram\Commands;

use Telegram\Bot\Actions;
use Telegram\Bot\Commands\Command;

class Start extends Command
{

    /**
     * @var string Command Name
     */
    protected $name = "start";

    /**
     * @var string Command Description
     */
    protected $description = "Запуск бота";

    /**
     * {@inheritdoc}
     */
    public function handle() {

        $text = "*Добро пожаловать!*\n";

        $update = $this->getUpdate();

        $chat_id = $update['message']['chat']['id'] ?? null;
        $first_name = $update['message']['chat']['first_name'] ?? null;
        $last_name = $update['message']['chat']['last_name'] ?? null;
        $username = $update['message']['chat']['username'] ?? null;

        $text .= $first_name ? "{$first_name} " : "";
        $text .= $last_name ? "{$last_name} " : "";
        $text .= $first_name || $last_name ? "\n" : "";

        $text .= $chat_id ? "*ID* `{$chat_id}`\n" : "";
        $text .= $username ? "*Логин* @{$username}\n" : "";

        $this->replyWithMessage([
            'text' => $text,
            'parse_mode' => "Markdown",
        ]);

        $this->triggerCommand('help');

    }

}