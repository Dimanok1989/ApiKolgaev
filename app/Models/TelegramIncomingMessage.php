<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramIncomingMessage extends Model
{
    
    /**
     * Атрибуты, которые назначаются массово.
     *
     * @var array
     */
    protected $fillable = [
        'message_id',
        'chat_id',
        'message',
        'request'
    ];

}
