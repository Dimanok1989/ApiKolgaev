<?php

namespace App\Jobs\Disk;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\Disk\DiskProcessArchive;

class CreateFolderArchive implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Время работы очереди
     * 
     * @var int
     */
    public $timeout = 0;

    /**
     * Список файлов
     * 
     * @var array
     */
    protected $files;

    /**
     * Уникальный идентификатор операции
     * 
     * @var array
     */
    protected $uid;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($files, $uid)
    {
        
        $this->files = $files;
        $this->uid = $uid;

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        
        $temp_dir = storage_path("app/drive/temp");
        $json_file = storage_path("app/drive/temp/{$this->uid}.json");

        $json = fopen($json_file, 'w');
        fwrite($json, json_encode($this->files));
        fclose($json);

        $createZip = app_path('Http/Controllers/Disk/CreateZip.py');
        $cmd = "python $createZip {$this->uid} {$temp_dir}";
        shell_exec($cmd);

        if ($process = DiskProcessArchive::where('uid', $this->uid)->get()[0] ?? null) {

            $process->created_done = date("Y-m-d H:i:s");
            $process->save();

        }

        unlink($json_file);

        \App\Events\Disk::dispatch([
            'archive' => $process,
        ]);

    }

}
