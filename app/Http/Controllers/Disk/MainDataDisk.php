<?php

namespace App\Http\Controllers\Disk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\User;
use App\DiskFile;
use App\Models\Disk\DiskFilesThumbnail;
use App\Models\Disk\DiskFilesLog;

class MainDataDisk extends Controller
{

    /**
     * Массив расширений для определения иконки
     * 
     * @var array $exts
     */
    public static $exts = [
        ['JPG','JPEG','SVG','PNG','BMP'],
        ['MOV','AVI','MP4','WEBM','MKV','M4V'],
        ['ZIP','XZ','BZ2'],
        ['RAR'],
        ['TXT'],
        ['RTF','DOC','DOCX'],
        ['XLS','CSV'],
        ['MP3','WAV','OGG'],
        ['PDF'],
        ['PHP','XML','VUE','SQL'],
        ['JS'],
        ['CSS'],
        ['HTML'],
        ['EXE','MSI'],
        ['7Z'],
    ];

    /**
     * Массив соотношения массива иконок с идентификатором иконки
     * 
     * @var array $icons
     */
    public static $icons = [
        0 => 'image',
        1 => 'video',
        2 => 'zip',
        3 => 'rar',
        4 => 'txt',
        5 => 'docx',
        6 => 'xls',
        7 => 'audio',
        8 => 'pdf',
        9 => 'code',
        10 => 'js',
        11 => 'css',
        12 => 'html',
        13 => 'exe',
        14 => 'sevez',
    ];

    /**
     * Лимит размера файлов
     * 
     * @var int
     */
    public static $limit = 500 * 1024 * 1024 * 1024;
    
    /**
     * Метод получения списка пользователей, доступным файловый менеджер
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function getUsersList(Request $request) {

        // Поиск пользователей, доступным раздел диска
        // $users = User::getUsersListForDisk();
        $users = User::permission('disk')->get();

        $sizes = []; // Размер файлов по каждому пользователю
        $size = 0; // Общий объем файлов

        $data = DiskFile::select(\DB::raw('SUM(size) as size, user'))
        ->where('deleted_at', NULL)
        ->groupBy('user')
        ->get();

        foreach ($data as $row) {
            $sizes[$row->user] = (int) $row->size;
            $size += (int) $row->size;
        }

        $usersList = [];
        foreach ($users as $user) {

            $usersList[] = [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'surname' => $user->surname,
                'patronymic' => $user->patronymic,
                'login' => $user->login,
                'size' => $sizes[$user->id] ?? 0,
                'sizeFormat' => parent::formatSize($sizes[$user->id] ?? 0),
            ];

        }

        $free = self::$limit - $size;

        return response([
            'users' => $usersList,
            'sizes' => [
                'size' => $size,
                'sizeFormat' => parent::formatSize($size),
                'limit' => self::$limit,
                'limitFormat' => parent::formatSize(self::$limit),
                'free' => $free,
                'freeFormat' => parent::formatSize($free),
            ],
        ]);

    }

    /**
     * Метод вывода файлов пользователя
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function getUserFiles(Request $request) {

        // Проверка идентификатора
        if (!$request->id)
            return response(['message' => "Ошибка идентификатора пользователя"], 400);

        $in_dir = (int) $request->folder;
        $link = env('APP_URL') . "/disk/{$request->user()->remember_token}";

        $dirs = []; // Список каталогов
        $files = []; // Список файлов

        $data = DiskFile::where([
            ['user', $request->id],
            ['in_dir', $in_dir],
            ['deleted_at', NULL],
            ['delete_query', NULL],
        ])
        ->orderBy('is_dir', 'DESC')
        ->orderBy('name')
        ->paginate(72);

        $files_id = [];
        $thumbs = [];

        foreach ($data as $row)
            $files_id[] = $row->id;

        DiskFilesThumbnail::whereIn('file_id', $files_id)
        ->chunk(100, function($rows) use (&$thumbs) {
            foreach ($rows as $row) {
                $thumbs[$row->file_id] = $row;
            }
        });
        
        foreach ($data as $file) {

            if ($file->is_dir) {

                $file->size = null;
                $file->ext = "Папка";
                $file->icon = "folder";

                $file->time = date("d.m.Y H:i:s", strtotime($file->created_at));

                $dirs[] = $file;

            }
            else {

                $time = false;

                $file->size = parent::formatSize($file->size);
                $file->icon = self::getFileIcon($file);

                // if (Storage::disk('public')->exists($file->path . "/" . $file->real_name))
                //     $time = Storage::disk('public')->lastModified($file->path . "/" . $file->real_name);

                // Создание ссылок на миниатюры
                $thumb = $thumbs[$file->id] ?? null;

                if ($thumb) {
                    // $file->thumb_middle = Storage::disk('public')->url($file->thumb_paht . "/" . $file->thumb_middle);
                    // $file->thumb_litle = $link . "?file={$file->id}&thumb=litle";

                    $file->thumb_litle = Storage::disk('public')->url($thumb->paht . "/" . $thumb->litle);
                    $file->thumb_middle = $link . "?file={$file->id}&thumb=middle";

                }

                // $file->time = $time ? date("d.m.Y H:i:s", $time) : false;
                $file->time = date("d.m.Y H:i:s", strtotime($file->created_at));

                $files[] = $file;

            }

        }

        // Поиск пути до каталога
        $paths = [];
        while ($in_dir) {

            $path = DiskFile::find($in_dir);

            $paths[] = [
                'id' => $path->id,
                'name' => $path->name,
            ];
            
            $in_dir = (int) $path->in_dir;

        }

        return response([
            'dirs' => $dirs,
            'files' => $files,
            'cd' => "",
            'paths' => array_reverse($paths),
            'next' => $data->currentPage() + 1,
            'last' => $data->lastPage(),
            $thumbs
        ]);

    }

    /**
     * Метод определения наименования иконки для расширения файла
     * 
     * @param object $file объект строки файла
     * @return string наименование иконки файла
     */
    public static function getFileIcon($file) {

        if ($file->is_dir)
            return "folder";

        $EXT = mb_strtoupper($file->ext);
        $searched = false;

        foreach (self::$exts as $key => $exts) {

            foreach ($exts as $ext) {
                if ($EXT == $ext) {
                    $searched = $key;
                    break;
                }
            }

            if ($searched !== false)
                break;
        }

        if ($searched !== false AND isset(self::$icons[$searched]))
            return self::$icons[$searched];

        return "file";

    }

    /**
     * Создание нового каталога
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function mkdir(Request $request) {

        $file = new DiskFile;

        $file->name = "Новая папка";
        $file->user = $request->user()->id;
        $file->is_dir = 1;
        $file->in_dir = (int) $request->cd;

        $file->save();

        $file->ext = "Папка";
        $file->time = date("d.m.Y H:i:s");
        $file->icon = "folder";

        \App\Events\Disk::dispatch((object) [
            'mkdir' => $file,
            'user' => (int) $file->user,
            'socketId' => $request->header('X-Socket-Id'),
        ]);

        DiskFilesLog::create([
            'user_id' => $request->user()->id,
            'file_id' => $file->id,
            'type' => "mkdir",
        ]);

        return response([
            'file' => $file,
        ]);

    }

    /**
     * Получение имени файла
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function getNameFile(Request $request) {

        if (!$request->id)
            return response(['message' => "Не найден идентификатор файла"], 400);

        if (!$file = DiskFile::find($request->id))
            return response(['message' => "Файл не найден"], 400);

        if ($file->deleted_at)
            return response(['message' => "Этот файл уже удалили"], 400);

        return response([
            'name' => $file->name
        ]);

    }

    /**
     * Переименовывание файла
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function rename(Request $request) {

        if (!$request->name)
            return response(['message' => "Пустое имя файла"], 400);

        // if (preg_match("/(^[a-zA-Z0-9]+([a-zA-Zа-яА-Я\_0-9\.-]*))$/", $request->name))
        //     return parent::error("Недопустимое имя файла");

        if (!$file = DiskFile::find($request->id))
            return response(['message' => "Файл не найден"], 400);

        if ($file->user != $request->user()->id)
            return response(['message' => "Этот файл нельзя переименовать"], 403);

        $file->name = $request->name;
        $file->save();

        $name = $file->name . ($file->ext ? "." . $file->ext : '');

        \App\Events\Disk::dispatch([
            'rename' => $file,
            'user' => (int) $file->user,
            'socketId' => $request->header('X-Socket-Id'),
        ]);
        
        return response([
            'name' => $name
        ]);

    }

    /**
     * Удаление файла
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function deleteFile(Request $request) {

        if (!$file = DiskFile::find($request->id))
            return response(['message' => "Файл не найден"], 400);

        if ($file->user != $request->user()->id)
            return response(['message' => "Этот файл нельзя удалить"], 403);

        $file->delete_query = date("Y-m-d H:i:s");
        $file->save();

        \App\Events\Disk::dispatch([
            'delete' => $file,
            'user' => (int) $file->user,
            'socketId' => $request->header('X-Socket-Id'),
        ]);

        return response([
            'message' => "Файл перемещен в корзину",
        ]);

    }

    /**
     * Метод возвращает ссылку на изображение среднего качества для его просмотра на сайте
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function showImage(Request $request) {

        if ($request->step)
            return self::getStepImage($request);

        $files = DiskFile::where([
            ['id', $request->id],
            ['is_dir', 0],
            ['deleted_at', NULL],
            ['delete_query', NULL],
        ])
        ->limit(1)
        ->get();

        if (!$file = $files[0] ?? null)
            return response(['message' => "Фото не найдено, возможно его уже удалили"], 400);

        $thumbs = DiskFilesThumbnail::where('file_id', $file->id)->get();

        if (!$thumb = $thumbs[0] ?? null)
            return response(['message' => "Фото еще не обработано"], 400);

        return response([
            'link' => env('APP_URL') . "/disk/{$request->user()->remember_token}?file={$file->id}&thumb=middle",
            'name' => $file->name,
            'id' => $file->id,
        ]);

    }

    /**
     * Поиск следующего и предыдущего изображений
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function getStepImage($request) {

        DiskFile::select(
            'disk_files.*'
        )
        ->where([
            ['is_dir', 0],
            ['in_dir', (int) $request->folder],
            ['delete_query', NULL],
        ])
        ->join('disk_files_thumbnails', 'disk_files_thumbnails.file_id', '=', 'disk_files.id')
        ->orderBy('name')
        ->chunk(100, function ($rows) use (&$request) {

            foreach ($rows as $row) {

                // Запись объекта первого изображения а каталоге
                if (!$request->first)
                    $request->first = $row;

                // Вывод следюущего изображения
                if ($request->next) {
                    $request->file = $row;
                    return false;
                }

                // Флаг вывода следующего изобравжения
                if ($request->step == "next" AND $row->id == $request->id)
                    $request->next = $row->id;

                // Вывод предыдущего изображения
                if ($request->step == "back" AND $row->id == $request->id) {
                    $request->file = $request->back;
                    return false;
                }

                $request->back = $row;

            }

        });

        if (!$request->file) {

            if ($request->step == "next" AND $request->first)
                $request->file = $request->first;
            elseif ($request->step == "back") 
                $request->file = self::showImageEndSteps($request);
            else
                return response(['message' => "Фотокарточка не найдена"], 400);

        }

        if (!$request->file)
            return response(['message' => "Фотокарточка не найдена"], 400);

        return response([
            'link' => env('APP_URL') . "/disk/{$request->user()->remember_token}?file={$request->file->id}&thumb=middle",
            'name' => $request->file->name,
            'id' => $request->file->id,
        ]);

    }

    /**
     * Метод поиска первого и последнего изображений в каталоге
     * 
     * @param Illuminate\Http\Request $request
     * @return array
     */
    public static function showImageEndSteps($request) {

        $file = DiskFile::select('disk_files.*')
        ->where([
            ['is_dir', 0],
            ['in_dir', (int) $request->folder],
            ['deleted_at', NULL],
            ['delete_query', NULL],
        ])
        ->join('disk_files_thumbnails', 'disk_files_thumbnails.file_id', '=', 'disk_files.id')
        ->limit(1)
        ->orderBy('name', 'DESC')
        ->get();

        return $file[0] ?? false;

    }

}
