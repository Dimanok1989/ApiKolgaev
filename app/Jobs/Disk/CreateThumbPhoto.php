<?php

namespace App\Jobs\Disk;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\DiskFile;

class CreateThumbPhoto implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Объект модели файла
     * 
     * @var \App\DiskFile
     */
    protected $file;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($file = null)
    {
        
        $this->file = DiskFile::find($file);

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        
        if (preg_match('/image\/*/', $this->file->mime_type)) {

            $thumb = new \App\Http\Controllers\Disk\Images;

            if (in_array($this->file->mime_type, $thumb->mime_types)) {
                $thumb->resizeFile($this->file, true);
            }

        }
        else if (preg_match('/video\/*/', $this->file->mime_type)) {

            $thumb = new \App\Http\Controllers\Disk\VideoConverter;
            $thumb->createPoster($this->file);

        }

    }
}
