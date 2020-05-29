<?php

namespace App\Http\Controllers\Disk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        $newfile->name = $file->getClientOriginalName(); // Имя файла для вывода
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

        if (!$file->storeAs($dir, $newfile->real_name, 'local'))
            return parent::error("Файл не загружен", 400);

        $newfile->save();

        $newfile->size_disp = parent::formatSize($newfile->size);

        return response([
            'file' => $newfile,
        ]);

    }

}
