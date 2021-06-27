<?php

namespace App\Http\Controllers\Telegram\Commands;

use Telegram\Bot\Actions;
use Telegram\Bot\Commands\Command;

class Help extends Command
{

    /**
     * @var string Command Name
     */
    protected $name = "help";

    /**
     * @var string Command Description
     */
    protected $description = "Список всех команд";

    /**
     * {@inheritdoc}
     */
    public function handle() {

        $text = "Используйте следующие команды:\n";

        $commands = $this->telegram->getCommands();

        foreach ($commands as $name => $handler) {
            /* @var Command $handler */
            $text .= sprintf('/%s - %s'.PHP_EOL, $name, $handler->getDescription());
        }

        $this->replyWithMessage([
            'text' => $text,
            'parse_mode' => "Markdown",
        ]);

    }

}