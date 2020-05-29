<?php

namespace App\Http\Controllers\Disk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadFile extends Controller
{
    
    public static function upload(Request $request) {

        if (!$request->user)
            return parent::error("Нет идентификатора", 400);

        // Путь до каталога с файлами пользователя
        $dir = "drive/" . $request->user;
        
        // Путь до подкаталога
        if ($request->cd)
            $dir .= $request->cd;

        $file = $request->file('files');

        $name = $file->getClientOriginalName();
        $size = parent::formatSize($file->getSize());
        $ext = $file->getClientOriginalExtension();

        $path_parts = pathinfo($name);
        $clearname = $path_parts['filename'];

        $count = 1;
		while (Storage::disk('public')->exists("{$dir}/{$name}")) {
            $name = $clearname . " (" . $count . ")." . $ext;
            $count++;
		}

        $path = $file->storeAs($dir, $name, 'public');

        return response([
            'path' => public_path($path),
            'name' => $name,
            'size' => $size,
            'ext' => $ext,
            'link' => Storage::disk('public')->url("{$dir}/{$name}"),
        ]);

    }

}
