<?php

namespace App\Http\Controllers\Disk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\DiskFile;
use App\Models\Disk\DiskFilesLog;

class Logs extends Controller
{
    
    /**
     * Метод вывода лога операций пользователей на главной странице диска
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function getLogs(Request $request) {

        $rows = DiskFilesLog::select(
            'disk_files_logs.*',
            'disk_files.name as file',
            'users.name',
            'users.surname'
        )
        ->join('users', 'users.id', '=', 'disk_files_logs.user_id')
        ->join('disk_files', 'disk_files.id', '=', 'disk_files_logs.file_id')
        ->whereNotIn('type', ['download'])
        ->orderBy('id', 'DESC')->limit(30)->get();

        $ids = [];

        foreach ($rows as $row) {
            if ($row->operation_id AND !in_array($row->operation_id, $ids))
                $ids[] = $row->operation_id;
        }

        $counts = DiskFile::select(
            \DB::raw('COUNT(operation_id) as count, operation_id')
        )
        ->whereIn('operation_id', $ids)
        ->groupBy('operation_id')
        ->get();

        $files = [];

        foreach ($counts as $count) {
            $files[$count->operation_id] = $count->count;
        }

        foreach ($rows as $row) {

            $row->count = $files[$row->operation_id] ?? 0;

            $user = $row->name ?? "";
            $user .= ($user == "" ? "" : " ") . ($row->surname ?? "");

            $time = strtotime($row->created_at);

            $logs[] = (object) [
                'id' => $row->id,
                'user' => $user,
                'date' => date("d.m.Y", $time),
                'time' => date("H:i", $time),
                'type' => $row->type,
                'file' => $row->file,
                'comment' => $row->comment,
                'count' => $row->count,
                'user_id' => $row->user_id,
                'file_id' => $row->file_id,
            ];

        }

        return response([
            'logs' => $logs ?? [],
        ]);

    }

}
