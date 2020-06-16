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

        $dir = "drive/" . date("Y/m/d"); // Путь до файла

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

        $put = Storage::disk('local')->putFileAs($dir, $file, $newfile->real_name);
        // $put = $file->move($dir . "/" . $newfile->real_name);
        
        // if (!$file->storeAs($dir, $newfile->real_name, 'local'))
        if (!$put)
            return parent::error("Ошибка сохранения файла", 400);

        // if (!$file->storeAs($dir, $newfile->real_name, 'local'))
        //     return parent::error("Файл не загружен", 400);

        $newfile->save();

        $newfile->size = parent::formatSize($newfile->size);
        $newfile->time = date("d.m.Y H:i:s");

        return response([
            'file' => $newfile,
        ]);

    }

}
