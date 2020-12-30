<?php

namespace App\Http\Controllers\Disk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\User;
use App\DiskFile;
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

        $dirs = []; // Список каталогов
        $files = []; // Список файлов

        $data = DiskFile::select(
            'disk_files.*',
            'disk_files_thumbnails.paht as thumb_paht',
            'disk_files_thumbnails.litle as thumb_litle',
            'disk_files_thumbnails.middle as thumb_middle'
        )
        ->where([
            ['in_dir', $in_dir],
            ['user', $request->id],
            ['deleted_at', NULL],
            ['delete_query', NULL],
        ])
        ->leftjoin('disk_files_thumbnails', 'disk_files_thumbnails.file_id', '=', 'disk_files.id')
        ->orderBy('is_dir', 'DESC')
        ->orderBy('name')
        ->paginate(72);

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
                if ($file->thumb_litle) {
                    $file->thumb_litle = Storage::disk('public')->url($file->thumb_paht . "/" . $file->thumb_litle);
                    $file->thumb_middle = Storage::disk('public')->url($file->thumb_paht . "/" . $file->thumb_middle);
                }

                $file->time = $time ? date("d.m.Y H:i:s", $time) : false;

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
    public static function showImageOld(Request $request) {

        $file = DiskFile::selectRaw(
            "REPLACE(REPLACE(name, ' ', ''), '_', '') as sort, disk_files.*, disk_files_thumbnails.paht as thumb_paht, disk_files_thumbnails.litle as thumb_litle, disk_files_thumbnails.middle as thumb_middle"
        )
        ->where([
            ['deleted_at', NULL],
            ['delete_query', NULL],
            ['is_dir', 0],
            ['in_dir', (int) $request->folder]
        ])
        ->join('disk_files_thumbnails', 'disk_files_thumbnails.file_id', '=', 'disk_files.id');

        if ($request->step == "next")
            $file = $file->where('disk_files.id', '>', $request->id)->orderBy('sort');
        elseif ($request->step == "back")
            $file = $file->where('disk_files.id', '<', $request->id)->orderBy('sort', 'DESC');
        else        
            $file = $file->where('disk_files.id', $request->id)->orderBy('sort');

        $file = $file->limit(1)->get();

        if (!count($file) AND $request->step)
            $file = self::showImageEndSteps($request);

        if (!count($file))
            return response(['message' => "Фотокарточка не найдена или её миниатюра еще не создана"], 400);

        return response([
            'link' => Storage::disk('public')->url($file[0]->thumb_paht . "/" . $file[0]->thumb_middle),
            'name' => $file[0]->name,
            'id' => $file[0]->id,
            'sort' => $file[0]->sort,
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
            'disk_files_thumbnails.paht as thumb_paht',
            'disk_files_thumbnails.litle as thumb_litle',
            'disk_files_thumbnails.middle as thumb_middle'
        )
        ->where([
            ['deleted_at', NULL],
            ['delete_query', NULL],
            ['is_dir', 0],
            ['in_dir', (int) $request->folder]
        ])
        ->join('disk_files_thumbnails', 'disk_files_thumbnails.file_id', '=', 'disk_files.id')
        ->limit(1);

        if ($request->step == "next")
            $file = $file->orderBy('name');
        elseif ($request->step == "back")
            $file = $file->orderBy('name', 'DESC');

        $file = $file->get();

        if ($request->findLast)
            return $file[0] ?? false;

        return $file;

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

        $file = DiskFile::select(
            'disk_files.*',
            'disk_files_thumbnails.paht as thumb_paht',
            'disk_files_thumbnails.litle as thumb_litle',
            'disk_files_thumbnails.middle as thumb_middle'
        )
        ->where([
            ['deleted_at', NULL],
            ['delete_query', NULL],
            ['is_dir', 0],
            ['disk_files.id', $request->id],
        ])
        ->join('disk_files_thumbnails', 'disk_files_thumbnails.file_id', '=', 'disk_files.id')
        ->limit(1)
        ->get();

        if (!count($file))
            return response(['message' => "Фотокарточка не найдена или её миниатюра еще не создана"], 400);

        return response([
            'link' => Storage::disk('public')->url($file[0]->thumb_paht . "/" . $file[0]->thumb_middle),
            'name' => $file[0]->name,
            'id' => $file[0]->id,
        ]);

    }

    /**
     * Поиск следующего и предыдущего изображений
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function getStepImage($request) {

        $file = false; // Объект данных найдного изображения
        $first = false; // Первое изображение в каталоге
        $next = false; // Переход к следующему изображению
        $back = false; // Переход к предыдущему изображению
        $request->all = [];
        $request->count = 0;

        DiskFile::select(
            'disk_files.*',
            'disk_files_thumbnails.paht as thumb_paht',
            'disk_files_thumbnails.litle as thumb_litle',
            'disk_files_thumbnails.middle as thumb_middle'
        )
        ->where([
            ['deleted_at', NULL],
            ['delete_query', NULL],
            ['is_dir', 0],
            ['in_dir', (int) $request->folder],
        ])
        ->join('disk_files_thumbnails', 'disk_files_thumbnails.file_id', '=', 'disk_files.id')
        ->orderBy('disk_files.name')
        ->chunk(50, function ($rows) use (&$request) {

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
            elseif ($request->step == "back") {
                $request->findLast = true;
                $request->file = self::showImageEndSteps($request);
            }
            else
                return response(['message' => "Фотокарточка не найдена"], 400);

        }

        return response([
            'link' => Storage::disk('public')->url($request->file->thumb_paht . "/" . $request->file->thumb_middle),
            'name' => $request->file->name,
            'id' => $request->file->id,
        ]);

    }

}
