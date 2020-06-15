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
    
    public static function getUsersList(Request $request) {

        // Поиск пользователей, доступным раздел диска
        $users = User::getUsersListForDisk();

        return response([
            'users' => $users,
        ]);

    }

    /**
     * Метод вывода файлов пользователя
     */
    public static function getUserFiles(Request $request) {

        if (!$request->id)
            return parent::error("Нет идентификатора", 400);

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

                $file->time = date("d.m.Y H:i:s", strtotime($file->created_at));

                $dirs[] = $file;

            }
            else {

                $file->size = parent::formatSize($file->size);

                $time = Storage::disk('local')->lastModified($file->path . "/" . $file->real_name);
                $file->time = date("d.m.Y H:i:s", $time);

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
     * Создание нового каталога
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
