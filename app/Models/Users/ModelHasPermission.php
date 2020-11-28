<?php

namespace App\Models\Users;

use Illuminate\Database\Eloquent\Model;

class ModelHasPermission extends Model
{
    /**
     * Определяет необходимость отметок времени для модели.
     *
     * @var bool
     */
    public $timestamps = false;
}
