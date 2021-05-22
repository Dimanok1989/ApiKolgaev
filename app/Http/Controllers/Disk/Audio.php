<?php

namespace App\Http\Controllers\Disk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

use App\Models\Disk\DiskFile;

class Audio extends Controller
{
    
    /**
     * Поиск файла и генерация временной ссылки на него
     * 
     * @param Illuminate\Http\Request
     * @return response
     */
    public static function playAudio(Request $request) {

        if (!$audio = DiskFile::find($request->audio))
            return response(['message' => "Аудиофайл не найден"], 400);

        $url = URL::temporarySignedRoute(
            'play.audio', now()->addMinutes(30), ['audio' => $request->audio]
        );

        return response()->json([
            'audio' => $audio,
            'url' => $url,
            'time' => time(),
        ]);

    }

    /**
     * Вывод аудиофайла
     * 
     * @param Illuminate\Http\Request
     * @return mix
     */
    public static function getFile(Request $request) {

        if (!$request->hasValidSignature()) {
            abort(401);
        }

        if (!$audio = DiskFile::find($request->audio))
            return abort(404);

        $path = storage_path("app/" . $audio->path . "/" . $audio->real_name);

        return response()->file($path, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS'
        ]);

    }

    /**
     * Смена аудио трека
     * 
     * @param Illuminate\Http\Request
     * @return response
     */
    public static function playAudioChange(Request $request) {

        if ($request->change == "next")
            return self::playNextAudio($request);

        if (!$audio = DiskFile::find($request->change))
            return response(['message' => "Песня не найдена"], 400); 
            
        return response()->json([
            'id' => $audio->id ?? null,
        ]);

    }

    /**
     * Поиск следующего трека
     * 
     * @param Illuminate\Http\Request
     * @return response
     */
    public static function playNextAudio($request) {

        // if (!$folder = DiskFile::find($request->folder))
        //     return response(['message' => "Ошибка выбранного каталога"], 400);

        $count = DiskFile::where([
            ['mime_type', 'LIKE', 'audio/%'],
            ['in_dir', $request->folder],
            ['id', '!=', $request->audio]
        ])
        ->count();

        if (!$count)
            return response(['message' => "Песня не найдена", 'count' => $count], 400);

        $offset = rand(1, $count);

        $file = DiskFile::where([
            ['mime_type', 'LIKE', 'audio/%'],
            ['in_dir', $folder->id],
            ['id', '!=', $request->audio]
        ])
        ->offset($offset)
        ->limit(1)
        ->get();

        $audio = $file[0] ?? null;

        if (!$audio)
            return response(['message' => "Песня не найдена", 'file' => $file], 400);

        return response()->json([
            'id' => $audio->id ?? null,
        ]);

    }

}
