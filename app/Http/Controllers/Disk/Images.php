<?php

namespace App\Http\Controllers\Disk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Image;

use App\DiskFile;
use App\Models\Disk\DiskFilesThumbnail;

/**
 * Необходимо создать символьную сылку на каталог с миниатюрами
 * 
 * Для Windows
 * mklink /D "[...]\storage\app\public\thumbs" "[...]\storage\app\drive\thumbs\litle"
 * 
 * Для Linux
 * ln -s [...]\storage\app\drive\thumbs\litle [...]\storage\app\public\thumbs
 * 
 * [...] - Каталог с проектом
 */
class Images extends Controller
{

    /**
     * Список типов файлов, для которых нужно создавать миниатюры
     * 
     * @var array
     */
    protected $mime_types = [
        'image/jpeg',
        'image/png',
        'image/gif'
    ];

    /**
     * Ширина миниатюры
     * 
     * @var int
     */
    protected $litle = 200;

    /**
     * Ширина картинки для просмотра
     * 
     * @var int
     */
    protected $middle = 1600;

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
     * Определение параметров
     * 
     * @param array $options
     */
    public function __construct($options) {

        $this->start = microtime(1); // Время запуска скрипта
        $this->last = $this->start; // Время последней операции

        // Пауза между выполнением
        $this->sleep = $options['sleep'] ?? $this->sleep;

        // Отключение цикла
        $this->onestep = $options['onestep'] ?? $this->onestep;

    }

    /**
     * Запуск цикла в работу
     * 
     * @return array
     */
    public function resize() {

        $this->resizeFile(); // Поиск и обработка изображений

        if ($this->onestep)
            return null;

        while ($this->start > time() - 55) {

            if (microtime(1) - $this->last < $this->sleep)
                continue;

            $this->resizeFile();
            $this->last = microtime(true);

        }

        return null;

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

        $files = DiskFile::whereIn('mime_type', $this->mime_types)
        ->where('thumbnail_created', NULL)
        ->limit(1)
        ->get();

        $file = $files[0] ?? null;

        if (!$file) {
            echo date("[Y-m-d H:i:s]") . " Файлов не найдено\n";
            return null;
        }

        $file->thumbnail_created = date("Y-m-d H:i:s");
        $file->save();

        // Путь до каталога с фото к просмотру на сайте
        $middle_dir_prefix = "drive/thumbs/middle";
        $middle_dir = date("Y/m/d/H");

        // Проверка и создание каталога
        if (!Storage::exists("{$middle_dir_prefix}/{$middle_dir}"))
            Storage::makeDirectory("{$middle_dir_prefix}/{$middle_dir}");

        // Проверка наличия файла с именем
        $middle_name = md5($file->real_name . $this->middle) . "." . $file->ext; // Имя файла
        $count = 1;

        while (Storage::disk('public')->exists("{$middle_dir_prefix}/{$middle_dir}/{$middle_name}")) {
            $middle_name = md5($middle_name . $this->middle . $count) . "." . $file->ext;
            $count++;
        }

        // Путь до миниатюр (будут общедоступными)
        $litle_dir_prefix = "drive/thumbs/litle";
        $litle_dir = date("Y/m/d/H");

        // Проверка и создание каталога
        if (!Storage::exists("{$litle_dir_prefix}/{$middle_dir}"))
            Storage::makeDirectory("{$litle_dir_prefix}/{$middle_dir}");

        // Проверка наличия миниатюры с именем
        $litle_name = md5($file->real_name . $this->litle) . "." . $file->ext; // Имя файла
        $count = 1;

        while (Storage::exists("{$litle_dir_prefix}/{$litle_dir}/{$litle_name}")) {
            $litle_name = md5($litle_name . $this->litle . $count) . "." . $file->ext;
            $count++;
        }

        // Путь до файла-исходника
        $file_path = storage_path('app/' . $file->path . "/" . $file->real_name);

        $middle_path = storage_path("app/{$middle_dir_prefix}/{$middle_dir}"); // Путь до урезанной копии
        $litle_path = storage_path("app/{$litle_dir_prefix}/{$litle_dir}"); // Путь до миниатюры

        // Создание изображения на основе исходника
        $img = Image::make($file_path);

        // Чтение данных изображения
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

        // Создание урезанной копии для просмотра на сайте
        $middle = $img->resize($middleWidth, $middleHeight, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $middle->save("{$middle_path}/{$middle_name}", 60);
        echo date("[Y-m-d H:i:s]") . " MIDDLE {$file_path} ===> {$middle_path}/{$middle_name}\n";

        // Создание эскиза
        $litle = $img->resize($litleWidth, $litleHeight, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $litle->save("{$litle_path}/{$litle_name}", 60);
        echo date("[Y-m-d H:i:s]") . " LITLE {$file_path} ===> {$litle_path}/{$litle_name}\n";

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
            'user' => (int) $file->user,
        ]);

        return null;

    }

    /**
     * Метод поиска и удаления дубликатов созданных миниатюр
     * В момент завершения предыдущего выполнения скрипта и начала
     * нового затрагивался один и тотже файл
     */
    public function removeDuplicateThumbnails() {

        $files = \DB::select(\DB::raw('SELECT `file_id`, COUNT(*) c FROM disk_files_thumbnails GROUP BY `file_id` HAVING c > 1'));

        $ids = [];
        foreach ($files as $file)
            $ids[] = $file->file_id;

        if (!count($ids)) {
            echo "\033[0;33m" . "Дубликатов не найдено\n";
            return true;
        }

        $rows = DiskFilesThumbnail::whereIn('file_id', $ids)->get();

        foreach ($rows as $row)
            $thumbs[$row->file_id] = $row;

        foreach ($thumbs as $thumb) {

            echo "\033[0;37m" . "Файл {$thumb->file_id}\n";

            $rm = unlink(storage_path('app/public/' . $thumb->paht . "/" . $thumb->litle));
            
            if ($rm)
                echo "\033[32m" . "Файл {$thumb->paht}/{$thumb->litle} удален\n";
            else
                echo "\033[31m" . "Файл {$thumb->paht}/{$thumb->litle} не найден\n";

            $rm = unlink(storage_path('app/public/' . $thumb->paht . "/" . $thumb->middle));

            if ($rm)
                echo "\033[32m" . "Файл {$thumb->paht}/{$thumb->middle} удален\n";
            else
                echo "\033[31m" . "Файл {$thumb->paht}/{$thumb->middle} не найден\n";

            DiskFilesThumbnail::where('id', $thumb->id)->limit(1)->delete();

            echo "\n";

        }

        echo "\033[0;37m" . "Поиск дубликатов завершен\n";

        return true;

    }

}
