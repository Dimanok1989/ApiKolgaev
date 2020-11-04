<?php

namespace App\Http\Controllers\Disk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\User;
use App\DiskFile;

class MainDataDisk extends Controller
{

    public function __construct() {

        // $this->middleware('role:friend');

    }

    /**
     * Массив расширений для определения иконки
     * 
     * @var array $exts
     */
    public static $exts = [
        ['JPG','JPEG','SVG','PNG','BMP'],
        ['MOV','AVI','MP4','WEBM','MKV','M4V'],
        ['ZIP','7Z','XZ','BZ2'],
        ['RAR'],
        ['TXT'],
        ['RTF','DOC','DOCX'],
        ['XLS','CSV'],
        ['MP3','WAV','OGG'],
        ['PDF'],
        ['PHP','XML','VUE'],
        ['JS'],
        ['CSS'],
        ['HTML'],
        ['EXE','MSI'],
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
        // 4 => 'text',
        5 => 'docx',
        6 => 'xls',
        7 => 'audio',
        // 8 => 'pdf',
        9 => 'code',
        10 => 'js',
        11 => 'css',
        12 => 'html',
        13 => 'exe',
    ];
    
    /**
     * Метод получения списка пользователей, доступным файловый менеджер
     * 
     * @param Illuminate\Http\Request $request
     * @return Response
     */
    public static function getUsersList(Request $request) {

        // Поиск пользователей, доступным раздел диска
        $users = User::getUsersListForDisk();

        return response([
            'users' => $users,
        ]);

    }

    /**
     * Метод вывода файлов пользователя
     * 
     * @param Illuminate\Http\Request $request
     * @return Response
     */
    public static function getUserFiles(Request $request) {

        // Проверка идентификатора
        if (!$request->id)
            return parent::error("Ошибка идентификатора пользователя", 400);

        $in_dir = (int) $request->folder;

        $dirs = []; // Список каталогов
        $files = []; // Список файлов

        $data = DiskFile::where([
            ['in_dir', $in_dir],
            ['user', $request->id]
        ])
        ->orderBy('name')->get();

        foreach ($data as $file) {

            if ($file->is_dir) {

                $file->size = null;
                $file->ext = "Папка";
                $file->icon = "folder";

                $file->time = date("d.m.Y H:i:s", strtotime($file->created_at));

                $dirs[] = $file;

            }
            else {

                $file->size = parent::formatSize($file->size);
                $file->icon = self::getFileIcon($file);

                if (Storage::disk('local')->exists($file->path . "/" . $file->real_name))
                    $time = Storage::disk('local')->lastModified($file->path . "/" . $file->real_name);
                else
                    $time = false;

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
        ]);

    }

    /**
     * Метод определения наименования икноки для расширения файла
     * 
     * @param object $file объект строки файла
     * @return string наименование иконки файла
     */
    public static function getFileIcon($file) {

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
     * @return Response
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

        return response([
            'file' => $file,
        ]);

    }

    /**
     * Переименовывание файла
     * 
     * @param Illuminate\Http\Request $request
     * @return Response
     */
    public static function rename(Request $request) {

        if (!$request->name)
            return parent::error("Пустое имя файла");

        // if (preg_match("/(^[a-zA-Z0-9]+([a-zA-Zа-яА-Я\_0-9\.-]*))$/", $request->name))
        //     return parent::error("Недопустимое имя файла");
        
        $file = DiskFile::find($request->id);

        if (!$file)
            return parent::error("Файл не найден");

        if ($file->user != $request->user()->id)
            return parent::error("Этот файл нельзя переименовать");

        $file->name = $request->name;
        $file->save();
        
        return response([
            $file
        ]);

    }

}
