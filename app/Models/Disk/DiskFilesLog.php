<?php

namespace App\Models\Disk;

use Illuminate\Database\Eloquent\Model;

class DiskFilesLog extends Model
{
    
    /**
     * Атрибуты, которые назначаются массово.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'file_id', 'type', 'operation_id',
    ];

}
