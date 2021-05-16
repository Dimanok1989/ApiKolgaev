<?php

namespace App\Http\Controllers\Disk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\DiskFile;
use App\Models\Disk\DiskProcessArchive;
use App\Models\Disk\DiskFilesLog;

class DownloadFolder extends Controller
{

    /**
     * Список файлов в каталоге
     * 
     * @var array
     */
    public static $tree = [];
    
    /**
     * Метод анализа каталога для создания архива
     * 
     * @param Illuminate\Http\Request $request
     */
    public static function downloadFolder(Request $request) {

        if (!$folder = DiskFile::find($request->id))
            return response(['message' => "Выбранный каталог не найден"], 400);

        if (!$folder->is_dir)
            return response(['message' => "Выбранный файл не является каталогом"], 400);

        if ($folder->user != $request->user()->id AND $folder->hiden == 1)
            return response(['message' => "Этот каталог скрыт от общего доступа"], 400);

        $three = self::getFilesInDir($folder->id);

        $names = []; // Все файлы
        $files = []; // Данные для создания архива
        $size = 0;

        // Обработка 
        foreach ($three as $row) {

            if ($row->is_dir == 1)
                continue;

            $tree = $row->tree != "" ? $row->tree . "/" : "";
            $file = $tree . $row->name . ($row->ext ? "." . $row->ext : "");
            $count = 1;

            if (in_array($file, $names)) {
            
                while (in_array($file, $names)) {
                    $file = $tree . $row->name . " ({$count})" . ($row->ext ? "." . $row->ext : "");
                    $count++;
                }

            }

            $names[] = $file;

            $files[] = [
                'file' => $file,
                'path' => $row->path
            ];

            $size += $row->size;

        }

        if (!$size)
            return response(['message' => "В каталоге нет файлов"], 400);

        $uid = md5(microtime(1) . $request->id . $request->user()->id) . "-" . $request->header('X-Socket-Id');

        $process = DiskProcessArchive::create([
            'user_id' => $request->user()->id,
            'uid' => $uid,
            'name' => $folder->name . ".zip",
        ]);

        \App\Jobs\Disk\CreateFolderArchive::dispatch($files, $uid);

        $folder->downloads++;
        $folder->save();

        return response()->json([
            'message' => "Началась подготовка архива",
            'files' => $files ?? [],
            'process' => $process,
            'size' => parent::formatSize($size),
        ]);

    }

    /**
     * Рекурсия создания дерева файлов в каталоге
     * 
     * @param int $id Идентификатор файла
     * @param string $tree путь до каталога
     */
    public static function getFilesInDir($id, $tree = "") {

        $files = [];
        
        DiskFile::where('in_dir', $id)
        ->where('delete_query', null)
        ->chunk(100, function($rows) use (&$files) {
            foreach ($rows as $row) {
                $files[] = $row;
            }
        });

        foreach ($files as $file) {

            $name = $file->name;

            if ($file->ext)
                $name .= "." . $file->ext;

            $path = $file->is_dir ? null : storage_path("app/" . $file->path . "/" . $file->real_name);

            self::$tree[] = (object) [
                'id' => $file->id,
                'name' => $file->name,
                'ext' => $file->ext,
                'is_dir' => $file->is_dir,
                'tree' => $tree,
                'path' => str_replace('\\', "/", $path),
                'size' => $file->size,
            ];

            if ($file->is_dir) {

                $newtree = ($tree != "" ? $tree . "/" : "") . $file->name;
                self::getFilesInDir($file->id, $newtree);

            }

        }

        return self::$tree;

    }

    /**
     * Скачивание созданного архива и его удаление
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function downloadAndRemove(Request $request) {

        if (!$process = DiskProcessArchive::find($request->folder))
            return abort(404);

        if ($process->user_id != $request->user->id)
            return abort(403);

        $path = storage_path("app/drive/temp/{$process->uid}.zip");

        if (!file_exists($path))
            return abort(404);

        $process->downloaded = date("Y-m-d H:i:s");
        $process->save();

        return response()->download($path, $process->name)->deleteFileAfterSend(true);

    }

}
