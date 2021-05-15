<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ThumbnailsImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'disk:thumbnails {--sleep=4 : Пауза между поиском картинки} {--onestep : Отключение цикла в скрипте} {--all : Обработать все имеющиеся изображения}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Создание миниатюр для фотографий на диске';

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

        $thumbnails = new \App\Http\Controllers\Disk\Images($this->options());
        $thumbnails->resize();

        return 0;

    }
}
