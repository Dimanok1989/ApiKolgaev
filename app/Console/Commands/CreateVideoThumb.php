<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CreateVideoThumb extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'disk:videothumb {--all : Обработать сразу все необработанные видео файлы}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Создание миниатюры для видео';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        
        set_time_limit(70); // Увеличить время работы скрипта

        $videothumb = new \App\Http\Controllers\Disk\VideoConverter($this->options());
        $videothumb->start();

        return 0;

    }
}
