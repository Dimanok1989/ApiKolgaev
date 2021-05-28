<?php

namespace App\Models\Disk;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    
    /**
     * Атрибуты, которые назначаются массово.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'message',
        'created_at',
    ];

}
