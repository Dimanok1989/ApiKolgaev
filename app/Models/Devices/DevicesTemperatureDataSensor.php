<?php

namespace App\Models\Devices;

use Illuminate\Database\Eloquent\Model;

class DevicesTemperatureDataSensor extends Model
{
    
    /**
     * Определяет необходимость отметок времени для модели.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Атрибуты, которые должны быть преобразованы в даты.
     *
     * @var array
     */
    protected $dates = ['created_at'];

    /**
     * Атрибуты, которые назначаются массово.
     *
     * @var array
     */
    protected $fillable = [
        'sensor_id', 'temperature', 'created_at'
    ];

}
