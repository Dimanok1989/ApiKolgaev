<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ThumbnailsRemoveDuplicate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'disk:removedbl';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Удаление дубликатов созданных миниатюр';

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
        $thumbnails = new \App\Http\Controllers\Disk\Images(true);
        $thumbnails->removeDuplicateThumbnails();
    }
}
