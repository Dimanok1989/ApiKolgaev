<?php

namespace App\Http\Controllers\Devices;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Devices\DevicesTemperatureDataSensor;
use App\Models\Devices\DevicesTemperatureSensor;

class Temperature extends Controller
{
    
    /**
     * Запись температуры с датчика
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function writeTemperature(Request $request) {

        $row = DevicesTemperatureDataSensor::create([
            'created_at' => date("Y-m-d H:i:s"),
            'temperature' => $request->sensor,
        ]);

        return response()->json([
            'message' => "Данные записаны",
            'temperature' => $request->sensor,
            'ip' => $request->ip(),
        ]);

    }

}
