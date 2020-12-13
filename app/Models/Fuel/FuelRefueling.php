<?php

namespace App\Models\Fuel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FuelRefueling extends Model
{
    
    use SoftDeletes;

    /**
     * Атрибуты, которые должны быть преобразованы в даты.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * Атрибуты, которые назначаются массово.
     *
     * @var array
     */
    protected $fillable = [
        'car', 'date', 'mileage', 'liters', 'price', 'type', 'gas_station', 'full', 'lost'
    ];
    
}
