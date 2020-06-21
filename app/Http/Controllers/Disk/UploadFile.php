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

use App\DiskFile;

class UploadFile extends Controller
{

    public static function upload(Request $request) {

        if (!$request->user)
            return parent::error("Нет идентификатора", 400);

        $dir = $request->path ? $request->path : "drive/" . date("Y/m/d"); // Путь до файла
        
        $pathinfo = pathinfo($request->name);

        $file = new DiskFile; // Создание нового экземпляра строки бд

        $file->user = $request->user; // Принадлежность файла к пользователю
        $file->path = $dir; // Путь до каталога с файлом
        $file->name = $pathinfo['filename'];
        $file->ext = $pathinfo['extension'];
        $file->size = $request->size; // Размер файла в байтах
        $file->in_dir = (int) $request->cd; // Принадлежность к каталогу
        $file->real_name = md5($file->name) . "." . $file->ext; // Имя файла для хранения

        if ($request->hash)
            $file->real_name = $request->hash;
        else
            $file->real_name = self::createFile($dir, $file);

        if (Storage::disk('local')->exists("{$dir}/{$file->real_name}")) {

            $chunk = base64_decode($request->chunk);
            $path = storage_path('app/' . $dir . "/" . $file->real_name);

            $write = self::putChunk($path);
            $write->send($chunk);

        }

        if ($request->endchunk)
            $file->save();

        $file->size = parent::formatSize($file->size);
        $file->time = date("d.m.Y H:i:s");
        $file->is_dir = 0;

        return response([
            'hash' => $file->real_name,
            'path' => $file->path,
            'file' => $request->endchunk ? $file : false,
        ]);

    }

    public static function createFile($dir, $file) {

        $file->real_name = md5($file->name) . "." . $file->ext; // Имя файла для хранения
        
        // Проверка повторяющегося имени
        $count = 1;
		while (Storage::disk('local')->exists("{$dir}/{$file->real_name}")) {
            $file->real_name = md5($file->real_name . $count) . "." . $file->ext;
            $count++;
        }

        Storage::disk('local')->put("{$dir}/{$file->real_name}", "");

        return $file->real_name;

    }

    public static function putChunk($file) {

        $f = fopen($file, 'a');
        while (true) {
            $line = yield;
            fwrite($f, $line);
        }

    }
    
    public static function uploadOld(Request $request) {

        if (!$request->user)
            return parent::error("Нет идентификатора", 400);

        $dir = "drive/" . date("Y/m/d"); // Путь до файла

        return self::uploadNew($request);

        $file = $request->file('files'); // Объект с файлом

        $newfile = new DiskFile; // Создание нового экземпляра строки бд
        $newfile->user = $request->user; // Принадлежность файла к пользователю
        $newfile->path = $dir; // Путь до каталога с файлом
        // $newfile->name = $file->getClientOriginalName(); // Имя файла для вывода
        $newfile->name = basename($file->getClientOriginalName(), '.' . $file->getClientOriginalExtension());
        $newfile->ext = $file->getClientOriginalExtension(); // Расширение файла
		$newfile->mime_type = $file->getClientMimeType(); // Тип файла
        $newfile->size = $file->getSize(); // Размер файла в байтах
        $newfile->in_dir = (int) $request->cd; // Принадлежность к каталогу

        $newfile->real_name = md5($newfile->name) . "." . $newfile->ext; // Имя файла для хранения

        $count = 1;
		while (Storage::disk('local')->exists("{$dir}/{$newfile->real_name}")) {
            $newfile->real_name = md5($newfile->real_name . $count) . "." . $newfile->ext;
            $count++;
        }

        $path = $file->getRealPath();

        // $put = $file->storeAs($dir, $newfile->real_name, 'local');
        // $put = Storage::disk('local')->putFileAs($dir, $file, $newfile->real_name);
        // $put = $file->move(storage_path('app/' . $dir), $newfile->real_name);
        // $put = rename($path, storage_path('app/' . $dir) . "/" . $newfile->real_name);
        $put = true;

        if (!$put)
            return parent::error("Ошибка сохранения файла", 400);

        $newfile->save();

        $newfile->size = parent::formatSize($newfile->size);
        $newfile->time = date("d.m.Y H:i:s");

        return response([
            'file' => $newfile,
            'temp' => $path,
            'put' => $put,
            'request' => $request->all(),
        ]);

    }

}
