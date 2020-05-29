<?php

namespace App\Http\Controllers\Disk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\User;
use App\UserRole;
use App\UserPermission;

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

        // Путь до каталога с файлами пользователя
        $dir = "drive/" . $request->id;
        $full = public_path();

        // Путь до выбранного каталога
        $cd = "";

        if ($request->path)
            $cd .= str_replace($dir, "", $request->path);

        // Полный путь
        $path = $dir . $cd;


        // Поиск каталогов в дирректории
        $dirs = [];
        foreach (Storage::disk('public')->directories($path) as $directorie) {

            $dirs[] = [
                'name' => basename($directorie),
                'path' => $directorie,
                'ext' => "Папка",
                'size' => "",
                'time' => date("d.m.Y H:i:s", Storage::disk('public')->lastModified($directorie)),
                'user' => $request->user()->id,
            ];

        }

        // Поиск файлов в дирректории
        $files = [];
        foreach (Storage::disk('public')->files($path) as $file) {

            $info = new \SplFileInfo(public_path("storage/" . $file));

            $files[] = [
                'name' => basename($file),
                'path' => $file,
                'ext' => $info->getExtension(),
                'size' => parent::formatSize($info->getSize()),
                'time' => date("d.m.Y H:i:s", Storage::disk('public')->lastModified($file)),
                'link' => Storage::disk('public')->url($file),
                'user' => $request->user()->id,
            ];
            
        }

        $paths = [];
        foreach(explode("/", str_replace($dir, "", $path)) as $p)
            if ($p != "")
                $paths[] = $p;

        return response([
            'dirs' => $dirs,
            'files' => $files,
            'cd' => $cd,
            'paths' => $paths,
        ]);

    }

}
