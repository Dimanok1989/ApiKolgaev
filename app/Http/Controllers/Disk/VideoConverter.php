<?php

namespace App\Http\Controllers\Disk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Models\Disk\DiskFile;
use App\Models\Disk\DiskFilesThumbnail;

class VideoConverter extends Controller
{

    /**
     * Пауза между обработкой файлов
     * 
     * @var int
     */
    protected $sleep = 4;

    /**
     * Выполнение одного прохода скрипта
     * 
     * @var bool
     */
    protected $onestep = false;

    /**
     * Обработать все изображения
     * 
     * @var bool
     */
    protected $all = false;

    /**
     * Определение параметров
     * 
     * @param array $options
     */
    public function __construct($options = []) {

        $this->start = microtime(1); // Время запуска скрипта
        $this->last = $this->start; // Время последней операции

        // Пауза между выполнением
        $this->sleep = $options['sleep'] ?? $this->sleep;

        // Отключение цикла
        $this->onestep = $options['onestep'] ?? $this->onestep;

        // Обработать все изображения
        $this->all = $options['all'] ?? $this->all;

        if ($this->all)
            $this->onestep = true;

    }

    /**
     * Запуск процесса
     * 
     * @return array
     */
    public function start() {

        if ($this->all)
            return $this->createAllFiles();

        // $this->resizeFile(); // Поиск и обработка изображений

        // if ($this->onestep)
        //     return null;

        // while ($this->start > time() - 55) {

        //     if (microtime(1) - $this->last < $this->sleep)
        //         continue;

        //     $this->resizeFile();
        //     $this->last = microtime(true);

        // }

        return null;

    }

    /**
     * Поиск видефайлов для массового создания миниатюр 
     */
    public function createAllFiles() {

        \set_time_limit(0);
        
        DiskFile::where('mime_type', 'LIKE', "video/%")
        ->where('thumbnail_created', NULL)
        ->chunk(50, function($rows) {
            foreach ($rows as $file) {
                $this->createPoster($file);
            }
        });

    }
    
    /**
     * Создание миниатюры видео
     * 
     * @param App\Models\Disk\DiskFile $file
     * @return response
     */
    public function createPoster($file) {

        // Путь до каталога с фото к просмотру на сайте
        $middle_dir_prefix = "drive/thumbs/middle";
        $middle_dir = date("Y/m/d/H");

        // Проверка и создание каталога
        if (!Storage::exists("{$middle_dir_prefix}/{$middle_dir}"))
            Storage::makeDirectory("{$middle_dir_prefix}/{$middle_dir}");

        // Проверка наличия файла с именем
        $middle_name = md5($file->real_name . $file->id) . "middle.jpg"; // Имя файла
        $count = 1;

        while (Storage::disk('public')->exists("{$middle_dir_prefix}/{$middle_dir}/{$middle_name}")) {
            $middle_name = md5($middle_name . $file->id . "middle" . $count) . ".jpg";
            $count++;
        }

        // Путь до миниатюр (будут общедоступными)
        $litle_dir_prefix = "drive/thumbs/litle";
        $litle_dir = date("Y/m/d/H");

        // Проверка и создание каталога
        if (!Storage::exists("{$litle_dir_prefix}/{$litle_dir}"))
            Storage::makeDirectory("{$litle_dir_prefix}/{$litle_dir}");

        // Проверка наличия миниатюры с именем
        $litle_name = md5($file->real_name . $file->id) . ".jpg"; // Имя файла
        $count = 1;

        while (Storage::exists("{$litle_dir_prefix}/{$litle_dir}/{$litle_name}")) {
            $litle_name = md5($litle_name . $file->id . $count) . ".jpg";
            $count++;
        }

        // Путь до файла-исходника
        $file_path = storage_path("app/{$file->path}/{$file->real_name}");

        echo date("[Y-m-d H:i:s]") . "FILE {$file_path}\n";

        $middle_path = storage_path("app/{$middle_dir_prefix}/{$middle_dir}"); // Путь до урезанной копии
        $litle_path = storage_path("app/{$litle_dir_prefix}/{$litle_dir}"); // Путь до миниатюры

        $ffmpeg = \FFMpeg\FFMpeg::create([
            'ffmpeg.binaries'  => env('FFMPEG_BIN'), // the path to the FFMpeg binary
            'ffprobe.binaries' => env('FFPROBE_BIN'), // the path to the FFProbe binary
            'timeout'          => 3600, // the timeout for the underlying process
            'ffmpeg.threads'   => 12,   // the number of threads that FFMpeg should use
        ]);

        $video = $ffmpeg->open($file_path);
        $duration = (float) $video->getFFProbe()->streams($file_path)->videos()->first()->get('duration');

        $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds($duration > 3 ? 3 : $duration / 2))
            ->save("{$middle_path}/{$middle_name}");

        echo "MIDDLE {$middle_path}/{$middle_name}\n";

        $this->createThumbIcon("{$middle_path}/{$middle_name}", "{$litle_path}/{$litle_name}");

        $file->thumbnail_created = date("Y-m-d H:i:s");
        $file->save();

        // Сохранение информации об эскизах
        $thumbnails = new DiskFilesThumbnail;
        $thumbnails->file_id = $file->id;
        $thumbnails->litle = $litle_dir . "/" . $litle_name;
        $thumbnails->litle_path = $litle_dir_prefix;
        $thumbnails->litle_size = filesize("{$litle_path}/{$litle_name}");
        $thumbnails->middle = $middle_dir . "/" . $middle_name;
        $thumbnails->middle_path = $middle_dir_prefix;
        $thumbnails->middle_size = filesize("{$middle_path}/{$middle_name}");
        $thumbnails->save();

        \App\Events\Disk::dispatch([
            'thumbnails' => [
                'id' => $file->id,
                'litle' => Storage::disk('public')->url("thumbs/{$litle_dir}/{$litle_name}"),
                'middle' => "?file={$file->id}&thumb=middle",
            ],
            'in_dir' => $file->in_dir,
            'user' => (int) $file->user,
        ]);

    }

    /**
     * Создание иконки на основе кадра
     * 
     * @param string $path
     */
    public function createThumbIcon($path_file, $litle_file) {

        // Создание изображения на основе исходника
        $img = \Image::make($path_file);

        // Создание эскиза
        $litle = $img->resize(200, null, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $litle->save($litle_file, 60);

        echo "LITLE {$litle_file}\n";

    }

}
