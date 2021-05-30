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
        ['XLS','XLSX','CSV'],
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
     * Миниатюры для изображений
     * 
     * @var array
     */
    public static $thumbs = [];
    
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

        if ($request->id != $request->user()->id) {
            if (self::checkHidenDir($in_dir)) {
                return response(['message' => "Этот каталог находится внутри скрытого от общего доступа каталога"], 400);
            }
        }

        $where = [
            ['user', $request->id],
            ['in_dir', $in_dir],
            ['deleted_at', NULL],
            ['delete_query', NULL],
        ];

        if ($request->id != $request->user()->id)
            $where[] = ['hiden', 0];

        $data = DiskFile::where($where)
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

        self::$thumbs = $thumbs;
        
        foreach ($data as $file) {

            if ($file->is_dir) {
                $dirs[] = self::getFileRowData($file);
            }
            else {
                $files[] = self::getFileRowData($file, $link);
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
        ]);

    }

    /**
     * Проверка принадлежности к скрытому каталогу
     * 
     * @param int $in_dir
     * @return bool
     */
    public static function checkHidenDir(int $in_dir) : bool {

        if ($in_dir == 0)
            return false;

        $dir = DiskFile::find($in_dir);

        if ($dir->hiden == 1)
            return true;

        return self::checkHidenDir($dir->in_dir);

    }

    /**
     * Метод формирования данных одного файла
     * 
     * @param \App\DiskFile $file
     * @param string|null $link
     * @return object $file
     */
    public static function getFileRowData($file, $link = null) {

        if ($file->is_dir) {

            $file->size = null;
            $file->ext = "Папка";
            $file->icon = "folder";

            $file->time = date("d.m.Y H:i:s", strtotime($file->created_at));

        }
        else {

            $time = false;

            $file->size = parent::formatSize($file->size);
            $file->icon = self::getFileIcon($file);

            if (Storage::disk('public')->exists($file->path . "/" . $file->real_name))
                $time = Storage::disk('public')->lastModified($file->path . "/" . $file->real_name);

            // Создание ссылок на миниатюры
            $thumb = self::$thumbs[$file->id] ?? null;

            if ($thumb && $link) {
                $file->thumb_litle = Storage::disk('public')->url("thumbs/{$thumb->litle}");
                $file->thumb_middle = $link . "?file={$file->id}&thumb=middle";
            }

            $time = $time ?? strtotime($file->created_at);
            $file->time = date("d.m.Y H:i:s", $time);

        }

        return $file;

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

        $file->name = $request->name ?? "Новая папка";
        $file->user = $request->user()->id;
        $file->is_dir = 1;
        $file->in_dir = (int) $request->cd;

        $file->save();

        $file->ext = "Папка";
        $file->time = date("d.m.Y H:i:s");
        $file->icon = "folder";

        broadcast(new \App\Events\Disk((object) [
            'mkdir' => $file,
            'user' => (int) $file->user,
        ]))->toOthers();

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
        
        return response()->json([
            'name' => $name,
            'onlyName' => $request->name,
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

        broadcast(new \App\Events\Disk([
            'delete' => $file,
            'user' => (int) $file->user,
        ]))->toOthers();

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
        
        if (preg_match('/video\/*/', $file->mime_type))
            return self::showVideo($request, $file);

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
            'disk_files.*',
            'disk_files_thumbnails.id as tumb_id'
        )
        ->leftjoin('disk_files_thumbnails', 'disk_files_thumbnails.file_id', '=', 'disk_files.id')
        ->where([
            ['is_dir', 0],
            ['in_dir', (int) $request->folder],
            ['delete_query', NULL],
        ])
        ->where(function($query) {
            $query->where('disk_files_thumbnails.id', '!=', null)
                ->orWhere('mime_type', 'LIKE', "video/%");
        })
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

        if (preg_match('/video\/*/', $request->file->mime_type))
            return self::showVideo($request, $request->file);

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

        $file = DiskFile::select(
            'disk_files.*',
            'disk_files_thumbnails.id as tumb_id'
        )
        ->leftjoin('disk_files_thumbnails', 'disk_files_thumbnails.file_id', '=', 'disk_files.id')
        ->where([
            ['is_dir', 0],
            ['in_dir', (int) $request->folder],
            ['delete_query', NULL],
        ])
        ->where(function($query) {
            $query->where('disk_files_thumbnails.id', '!=', null)
                ->orWhere('mime_type', 'LIKE', "video/%");
        })
        ->orderBy('name', 'DESC')
        ->limit(1)
        ->get();

        return $file[0] ?? false;

    }

    /**
     * Метод вывода информации для просмотра видео
     * 
     * @param Iluminate\Http\Request $request
     * @param App\Models\DiskFile $file
     * @return response
     */
    public static function showVideo($request, $file) {

        return response()->json([
            'id' => $file->id,
            'name' => $file->name,
            'video' => env('APP_URL') . "/disk/{$request->user()->remember_token}?file={$file->id}",
        ]);

    }

    /**
     * Скрыть/отобразить файл для общего доступа
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function hideFile(Request $request) {

        if (!$file = DiskFile::find($request->id))
            return response(['message' => "Файл не найден"], 400);

        if ($request->user()->id != $file->user)
            return response(['message' => "Доступ для скрытия этого файла есть только у его владельца"], 403);

        $file->hiden = $file->hiden == 0 || !$file->hiden ? 1 : 0;
        $file->save();

        DiskFilesThumbnail::where('file_id', $file->id)
        ->chunk(100, function($rows) {
            foreach ($rows as $row) {
                self::$thumbs[$row->file_id] = $row;
            }
        });

        $link = env('APP_URL') . "/disk/{$request->user()->remember_token}";
        $file = self::getFileRowData($file, $link);

        broadcast(new \App\Events\Disk([
            'hide' => $file,
            'user' => (int) $file->user,
        ]))->toOthers();

        return response()->json([
            'file' => $file,
        ]);

    }

}
