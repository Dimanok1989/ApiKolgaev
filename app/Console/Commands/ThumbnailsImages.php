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
    protected $signature = 'minute:thumbnails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Created thumbnails images for files disk';

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
        $thumbnails->resize();

    }
}
