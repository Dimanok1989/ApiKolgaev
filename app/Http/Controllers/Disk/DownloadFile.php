<?php

namespace App\Http\Controllers\disk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Http\Controllers\Disk\MainDataDisk;
use App\Http\Controllers\Disk\FileReader;

use App\DiskFile;
use App\Models\Disk\DiskFilesThumbnail;
use App\Models\Disk\DiskFilesLog;

class DownloadFile extends Controller
{

    /**
     * Список файлов в каталоге
     * 
     * @var array
     */
    public static $tree = [];

    /**
     * Метод проверки файла, сбора информации и её вывода
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function startDownload(Request $request) {

        if (!$file = DiskFile::find($request->id))
            return response(['message' => "Файл не найден"], 404);

        // Получение списка файлов для каталога
        if ($file->is_dir)
            self::getFilesInDir($file->id);

        $name = $file->name;
        $name .= "." . ($file->ext ?? "zip");

        $size = $file->size;

        foreach (self::$tree as $tree) {
            $size += $tree->size;
        }

        DiskFilesLog::create([
            'user_id' => $request->user()->id,
            'file_id' => $file->id,
            'type' => "download",
        ]);

        return response([
            'name' => $name,
            'is_dir' => $file->is_dir,
            'size' => $size,
            'sizeformat' => parent::formatSize($size),
            'count' => count(self::$tree),
            'icon' => MainDataDisk::getFileIcon($file),
        ]);

    }
    
    /**
     * Метод загрузки файла
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function download(Request $request) {

        if (!$file = DiskFile::find($request->id))
            return response(['message' => "Файл не найден"], 404);

        $file->downloads += 1;
        $file->save();

        if ($file->is_dir)
            return self::createArchive($file);

        $name = $file->name . "." . $file->ext;
        $path = storage_path("app/" . $file->path . "/" . $file->real_name);

        return response()->download($path, $name);

    }

    /**
     * Рекурсия создания дерева файлов в каталоге
     * 
     * @param int $id Идентификатор файла
     * @param string $tree путь до каталога
     */
    public static function getFilesInDir($id, $tree = "") {

        $files = [];
        
        DiskFile::where('in_dir', $id)->chunk(100, function($rows) use (&$files) {
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
                'name' => $name,
                'is_dir' => $file->is_dir,
                'tree' => $tree,
                'path' => $path,
                'size' => $file->size,
            ];

            if ($file->is_dir) {

                $newtree = ($tree != "" ? $tree . "/" : "") . $file->name;
                self::getFilesInDir($file->id, $newtree);

            }

        }

    }

    /**
     * Метод создания архива с файлами из каталога
     * 
     * @param object $file объект файла
     * @return response
     */
    public static function createArchive($file) {

        set_time_limit(0); // Увеличение времени работы скрипта

        self::getFilesInDir($file->id); // Список файлов в каталоге
        $tree = self::$tree; // Дерево файлов в каталоге

        return response()->stream(function() use ($file, $tree) {

            $options = new \ZipStream\Option\Archive();

            $options->setSendHttpHeaders(true);
            $options->setContentType("application/x-zip");
            $options->setContentDisposition("Content-Disposition: attachment; filename={$file->name}.zip");
            $date = date("d.m.Y в H:i:s");
            $options->setComment("Архив каталога {$file->name}\nСоздан $date\n\nhttps://disk.kolgaev.ru");

            $zip = new \ZipStream\ZipStream($file->name . ".zip", $options);

            // Запись файлов в архив
            foreach ($tree as $row) {

                if ($row->is_dir == 0) {

                    $fileName = ($row->tree != "" ? $row->tree . "/" : "") . $row->name;
                    $streamRead = fopen($row->path, 'r');

                    $zip->addFileFromStream($fileName, $streamRead);

                }
            }

            $zip->finish();

        });

    }

    /**
     * Метод вывода файла
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function getFileFromLink(Request $request) {

        $token = \App\User::where([
            ['remember_token', $request->token],
            ['email_verified_at', '>', date("Y-m-d H:i:s", time() - 60 * 60 * 3)]
        ])
        ->limit(1)
        ->get();

        $request->user = $token[0] ?? null;

        if (!$request->user)
            return abort(403);
            
        if ($request->thumb)
            return self::getThumb($request);

        return self::getFile($request);

    }

    /**
     * Метод вывода миниатюры фотографии
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function getThumb($request) {

        if (!in_array($request->thumb, ['litle', 'middle']))
            return abort(404);

        $row = DiskFilesThumbnail::select(
            'disk_files.user',
            'disk_files.hiden',
            'disk_files.is_dir',
            'disk_files.delete_query',
            'disk_files.deleted_at',
            'disk_files_thumbnails.litle',
            'disk_files_thumbnails.litle_path',
            'disk_files_thumbnails.middle',
            'disk_files_thumbnails.middle_path'
        )
        ->join('disk_files', 'disk_files.id', '=', 'disk_files_thumbnails.file_id')
        ->where('file_id', $request->file)
        ->get();

        $file = $row[0] ?? null;

        if (!$file)
            return abort(404);

        if ($file->is_dir == 1 || $file->delete_query || $file->deleted_at)
            return abort(404);

        if ($file->hiden == 1 AND $file->user != $request->user->id)
            return abort(403);

        if ($request->thumb == "litle")
            $path = storage_path("app/public/{$file->litle_path}/{$file->litle}");
        elseif ($request->thumb == "middle")
            $path = storage_path("app/{$file->middle_path}/{$file->middle}");
        else
            return abort(403);

        if (!file_exists($path))
            return abort(404);

        return response()->file($path);

    }

    /**
     * Метод вывода файла по запросу
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function getFile(Request $request) {

        if (!$file = DiskFile::find($request->file))
            return abort(404);
        
        if ($file->is_dir == 1 || $file->delete_query || $file->deleted_at)
            return abort(404);

        if ($file->hiden == 1 AND $file->user != $request->user->id)
            return abort(403);

        $path = storage_path("app/" . $file->path . "/" . $file->real_name);

        if (!file_exists($path))
            return abort(404);

        return response()->file($path);

    }

    /**
     * Скачивание файла
     * Ссылка для скачивания `{host}/download/{file_name}?file={file}&main_id={main_id}`
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function downloadFile(Request $request, $file_name) {

        $token = $_COOKIE['main_id'] ?? null;

        if ($token != $request->main_id)
            return abort(403);

        $token = \App\User::where([
            ['remember_token', $request->main_id],
            ['email_verified_at', '>', date("Y-m-d H:i:s", time() - 60 * 60 * 3)]
        ])
        ->limit(1)
        ->get();

        $request->user = $token[0] ?? null;

        if (!$request->user)
            return abort(403);

        if (!$file = DiskFile::find($request->file))
            return abort(404);

        if ($file->is_dir == 1 || $file->delete_query || $file->deleted_at)
            return abort(404);

        if ($file->hiden == 1 AND $file->user != $request->user->id)
            return abort(403);

        $path = storage_path("app/" . $file->path . "/" . $file->real_name);

        if (!file_exists($path))
            return abort(404);

        $file->downloads++;
        $file->save();

        return response()->download($path, "{$file->name}.{$file->ext}");

    }

    /**
     * Метод создания архива с файлами
     * Устарел и не используется
     * 
     * @param object $file Объект данных каталога
     * @return response
     */
    public static function createArchiveOld($file) {

        $path = "drive/temp"; // Путь до временного каталога
        $temp = storage_path("app/" . $path); // Полный путь до временного каталога

        // Проверка временного каталога
        Storage::disk('local')->makeDirectory($path);

        $zip = new \ZipArchive();
        $name = $temp . "/" . $file->name . ".zip";

        if (Storage::disk('local')->exists($path . "/" . $file->name . ".zip"))
            $deleted = Storage::disk('local')->delete($path . "/" . $file->name . ".zip");

        if ($zip->open($name, \ZipArchive::CREATE) !== true)
            return response(['message' => "Ошибка создания архива"], 400);

        // Список файлов в каталоге
        self::getFilesInDir($file->id);

        $zip->addFromString("info.txt", "Архив создан " . date("d.m.Y в H:i:s"));

        // // Запись файлов в архив
        // foreach (self::$tree as $file) {
        //     if ($file->is_dir == 0)
        //         $zip->addFile($file->path, ($file->tree != "" ? $file->tree . "/" : "") . $file->name);
        // }

        $zip->close();

        return response([
            // 'zip' => $zip,
            // 'file' => $file,
            'name' => $file->name . ".zip",
            'tree' => self::$tree,
            // 'deleted' => $deleted ?? null,
        ]);

    }

    public static function addFileToZip(Request $request) {

        $file = DiskFile::find($request->id);
        $path = storage_path("app/public/" . $file->path); // Полный путь до каталога с файлом

        // Откртиые файла
        $stream = new FileReader($path . "/" . $file->real_name);

        $line = $request->offset ?? 0; // Смещение строк чтения файла
        $stream->setOffset($line); // Смещение в файле

        $count_line = 200; // Колчиество строк для чтения
        $read = $stream->read($count_line); // Результат чтения

        if (count($read))
            $buffer = implode("\n", $read);
        else
            $endread = true;

        $temp = storage_path("app/public/drive/temp"); // Полный путь до временного каталога
        $name = $temp . "/" . $request->zipname;

        $options = new \ZipStream\Option\Archive();
        // $options->setSendHttpHeaders(true);

        $zip = new \ZipStream\ZipStream($name);

        // $zip = new \ZipArchive();

        // if ($zip->open($name) !== true)
        //     return response(['message' => "Ошибка открытия архива"], 400);


        $zipfile = $request->tree ? $request->tree . "/" : "";
        $zipfile .= $file->name . "." . $file->ext;

        $zip->addFile('hello.txt', 'This is the contents of hello.txt');

        // $zip->addFileFromPath($zipfile, $path . "/" . $file->real_name);

        // if ($line > 0) {
        //     $write = self::putChunk("zip://{$name}#{$zipfile}");
        //     $write->send($buffer ?? "");
        // }
        // else
        //     $zip->addFromString($zipfile, $buffer ?? "");

        // $zip->close();
        $zip->finish();

        return response([
            // 'request' => $request->all(),
            'name' => $name,
            'done' => $endread ?? false,
            'readed' => $line + $count_line,
            $zip
        ]);

    }

    public static function addFileToZipOld(Request $request) {

        $file = DiskFile::find($request->id);
        $path = storage_path("app/" . $file->path); // Полный путь до каталога с файлом

        // Откртиые файла
        $stream = new FileReader($path . "/" . $file->real_name);

        $line = $request->offset ?? 0; // Смещение строк чтения файла
        $stream->setOffset($line); // Смещение в файле

        $count_line = 200; // Колчиество строк для чтения
        $read = $stream->read($count_line); // Результат чтения

        if (count($read))
            $buffer = implode("\n", $read);
        else
            $endread = true;

        $temp = storage_path("app/drive/temp"); // Полный путь до временного каталога
        $name = $temp . "/" . $request->zipname;

        $zip = new \ZipArchive();

        if ($zip->open($name) !== true)
            return response(['message' => "Ошибка открытия архива"], 400);

        $zipfile = $request->tree ? $request->tree . "/" : "";
        $zipfile .= $file->name . "." . $file->ext;

        if ($line > 0) {
            $write = self::putChunk("zip://{$name}#{$zipfile}");
            $write->send($buffer ?? "");
        }
        else
            $zip->addFromString($zipfile, $buffer ?? "");

        $zip->close();

        return response([
            // 'request' => $request->all(),
            'done' => $endread ?? false,
            'readed' => $line + $count_line,
        ]);

    }

    /**
     * Генератор записи части файла
     * 
     * @param string $file путь до файла
     * @return object
     */
    public static function putChunk($file) {

        $f = fopen($file, 'r');
        while (true) {
            $line = yield;
            fwrite($f, $line);
        }

    }

}
