<?php

namespace App\Http\Controllers\Disk;

/*
|--------------------------------------------------------------------------
| Загрузка файлов в каталог пользователя
|--------------------------------------------------------------------------
|
| Скрипт принимает по одному файлу от авторизованного пользователя 
| и сохраняет их в общий каталог, после чего создается запись в БД с
| информацией о файле
|
| Для загрузки больших файлов необходимо установить настройку php
| upload_max_filesize и настроить сервер на передачу больших файлов
*/

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;

use App\Http\Controllers\Disk\MainDataDisk;

use App\DiskFile;
use App\Models\Disk\DiskFilesLog;


class UploadFile extends Controller
{

    /**
     * Метод загрузки части файла
     * 
     * @param Illuminate\Http\Request $request
     * @return Response
     */
    public static function upload(Request $request) {

        if (!$request->user)
            return response(['message' => "Нет идентификатора пользователя"], 400);

        $dir = $request->path ? $request->path : "drive/" . date("Y/m/d"); // Путь до файла
        
        $pathinfo = pathinfo($request->name);

        $file = new DiskFile; // Создание нового экземпляра строки бд

        $file->user = $request->user; // Принадлежность файла к пользователю
        $file->path = $dir; // Путь до каталога с файлом
        $file->name = $pathinfo['filename'];
        $file->ext = $pathinfo['extension'];
        $file->size = $request->size; // Размер файла в байтах
        $file->mime_type = $request->type;
        $file->in_dir = (int) $request->cd; // Принадлежность к каталогу
        $file->real_name = md5($file->name) . "." . $file->ext; // Имя файла для хранения

        if ($request->hash)
            $file->real_name = $request->hash;
        else
            $file->real_name = self::createFile($dir, $file);

        // Дополнение файла очередной частью
        if (Storage::disk('public')->exists("{$dir}/{$file->real_name}")) {

            $chunk = base64_decode($request->chunk);
            $path = storage_path('app/public/' . $dir . "/" . $file->real_name);

            $write = self::putChunk($path);
            $write->send($chunk);

        }

        // Завершение загрузки файла
        if ($request->endchunk)
            $file->save();

        $file->size = parent::formatSize($file->size);
        $file->time = date("d.m.Y H:i:s");
        $file->is_dir = 0;
        $file->icon = MainDataDisk::getFileIcon($file);

        if ($request->endchunk) {

            \App\Events\Disk::dispatch([
                'new' => $file,
                'user' => (int) $file->user,
                'socketId' => $request->header('X-Socket-Id'),
            ]);
            
        }

        return response([
            'hash' => $file->real_name,
            'path' => $file->path,
            'size' => Storage::disk('public')->size($dir . "/" . $file->real_name),
            'file' => $request->endchunk ? $file : false,
        ]);

    }

    /**
     * Метод создания файла в каталоге хранения файлов
     * 
     * @param string $dir путь до каталога с файлом
     * @param object $file объект данных файла
     * @return string
     */
    public static function createFile($dir, $file) {

        $file->real_name = md5($file->name) . "." . $file->ext; // Имя файла для хранения
        
        // Проверка повторяющегося имени
        $count = 1;
		while (Storage::disk('public')->exists("{$dir}/{$file->real_name}")) {
            $file->real_name = md5($file->real_name . $count) . "." . $file->ext;
            $count++;
        }

        Storage::disk('public')->put("{$dir}/{$file->real_name}", "");

        return $file->real_name;

    }

    /**
     * Генератор записи части файла
     * 
     * @param string $file путь до файла
     * @return object
     */
    public static function putChunk($file) {

        $f = fopen($file, 'a');
        while (true) {
            $line = yield;
            fwrite($f, $line);
        }

    }

}
