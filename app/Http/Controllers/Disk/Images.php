<?php

namespace App\Http\Controllers\Disk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Image;

use App\DiskFile;
use App\Models\Disk\DiskFilesThumbnail;

class Images extends Controller
{

    /**
     * Список типов файлов, для которых нужно создавать миниатюры
     * 
     * @var array
     */
    public $mime_types = [
        'image/jpeg', 'image/png', 'image/gif'
    ];

    /**
     * Ширина миниатюры
     * 
     * @var int
     */
    public $litle = 200;

    /**
     * Ширина картинки для просмотра
     * 
     * @var int
     */
    public $middle = 1600;

    /**
     * Определение вывода результата
     * 
     * @var bool
     */
    public $echo = false;

    /**
     * Определение параметров
     */
    public function __construct($echo = false) {

        $this->echo = $echo;

    }

    /**
     * Запуск цикла в работу в течение 1 минуты для повторного выполнения кроной
     * 
     * @return array
     */
    public function resize() {

        set_time_limit(70); // Увеличить время работы скрипта

        $start = $last = microtime(true); // Время старта
        $count = 0; // Счетчик прохода цикла

        while ($start > time() - 5) {

            if ($process = $this->resizeFile())
                $data[] = $process;

            $last = microtime(true);

            $count++;
            sleep(4); // Пауза для кменьшения нагрузки

        }

        $time = round(microtime(true) - $start, 2);

        if ($this->echo) {
            $files = count($data ?? []);
            echo "\033[1;33m" . "Обработано файлов {$files}\n";
            echo "\033[0;37m" . "Выполнено за $time сек\n";
            return null;
        }

        return response([
            'time' => $time,
            'count' => $count,
            'data' => $data ?? [],
        ]);

    }

    /**
     * Метод создания одной миниатюры
     */
    public function resizeFile() {

        $file = DiskFile::where('thumbnail_created', NULL)
        ->whereIn('mime_type', $this->mime_types)
        ->limit(1)
        ->get();

        if (!count($file))
            return null;

        $file = $file[0];

        $dir = $file->path . "/thumbnails";

        if (!Storage::disk('public')->exists($dir))
            Storage::disk('public')->makeDirectory($dir);

        $path = storage_path('app/public/' . $dir);
        $filepath = storage_path('app/public/' . $file->path . "/" . $file->real_name);

        $img = Image::make($filepath);

        $exif = $img->exif('COMPUTED');

        $w = $exif['Width'] ?? null;
        $h = $exif['Height'] ?? null;

        $litleWidth = $litleHeight = null;
        $middleWidth = $middleHeight = null;

        // Определение ширины и высоты
        if ($w !== null && $h !== null) {

            if ($w >= $h) {
                $litleWidth = $this->litle;
                $middleWidth = $this->middle;
            }
            else {
                $litleHeight = $this->litle;
                $middleHeight = $this->middle;
            }

        }
        else {
            $litleWidth = $this->litle;
            $middleWidth = $this->middle;
        }

        $filename = $file->real_name; // Имя файла
        $count = 1;

        while (Storage::disk('public')->exists("{$dir}/{$filename}")) {
            $filename = md5($filename . $count) . "." . $file->ext;
            $count++;
        }

        // Создание урезанной копии
        $middle = $img->resize($middleWidth, $middleHeight, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $middle->save("{$path}/{$filename}", 60);
        $nameMiddle = $filename;

        while (Storage::disk('public')->exists("{$dir}/{$filename}")) {
            $filename = md5($filename . $count) . "." . $file->ext;
            $count++;
        }

        // Создание эскиза
        $litle = $img->resize($litleWidth, $litleHeight, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $litle->save("{$path}/{$filename}", 60);
        $nameLitle = $filename;

        $filerow = DiskFile::find($file->id);
        $filerow->thumbnail_created = date("Y-m-d H:i:s");
        $filerow->save();

        $thumbnails = new DiskFilesThumbnail;
        $thumbnails->file_id = $file->id;
        $thumbnails->paht = $dir;
        $thumbnails->litle = $nameLitle;
        $thumbnails->litle_size = filesize("{$path}/{$nameLitle}");
        $thumbnails->middle = $nameMiddle;
        $thumbnails->middle_size = filesize("{$path}/{$nameMiddle}");
        $thumbnails->save();

        \App\Events\Disk::dispatch([
            'thumbnails' => [
                'litle' => Storage::disk('public')->url($dir . "/" . $nameLitle),
                'middle' => Storage::disk('public')->url($dir . "/" . $nameMiddle),
            ],
            'user' => (int) $file->user,
        ]);

        if ($this->echo) {
            echo "\033[32m" . "Создан эскиз фото {$file->path}/{$file->name}.{$file->ext}\n";
            return true;
        }

        return [
            'file' => $filerow,
            'thumbnails' => $thumbnails,
        ];

    }

}
