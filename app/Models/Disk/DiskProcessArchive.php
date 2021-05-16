<?php

namespace App\Models\Disk;

use Illuminate\Database\Eloquent\Model;

class DiskProcessArchive extends Model
{

    /**
     * Атрибуты, которые назначаются массово.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'uid',
        'name',
        'created_done',
        'downloaded',
    ];
    
}
