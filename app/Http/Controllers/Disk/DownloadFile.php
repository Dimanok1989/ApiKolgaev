<?php

namespace App\Http\Controllers\disk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\DiskFile;

class DownloadFile extends Controller
{
    
    public static function download(Request $request) {

        $file = DiskFile::find($request->id);

        $name = $file->name . "." . $file->ext;

        // Storage::disk('local')->exists("{$file->path}/{$file->real_name}")

        return Storage::disk('local')->download("{$file->path}/{$file->real_name}", $name);

    }

}
