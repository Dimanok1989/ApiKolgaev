<?php

namespace App\Http\Controllers\Fuel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\User;

use App\Models\Fuel\FuelCar;
use App\Models\Fuel\FuelRefueling;

class Fuel extends Controller
{
    
    /**
     * Получение данных для главной страницы расхода топлива
     */
    public static function getMainData(Request $request) {

        // Получение списка машин пользователя
        $cars = FuelCar::where('user', $request->user()->id)->get();

        // Сбор идентификаторов машин
        $in = [];
        foreach ($cars as $car)
            $in[] = $car->id;

        // Последние 10 заправок
        $fuels = FuelRefueling::select(
            'fuel_refuelings.*',
            'fuel_cars.brand',
            'fuel_cars.model',
            'fuel_cars.modification'
        )
        ->join('fuel_cars', 'fuel_cars.id', '=', 'fuel_refuelings.car')
        ->whereIn('fuel_refuelings.car', $in)
        ->orderBy('fuel_refuelings.mileage', 'DESC')
        ->limit(5)->get();

        foreach ($fuels as &$fuel)
            $fuel->date = date("d.m.Y", strtotime($fuel->date));

        return response([
            'cars' => $cars,
            'fuels' => $fuels,
        ]);

    }

    public static function getFuelsCar(Request $request) {

        $limit = 30;

        if (!$request->car)
            return response(['message' => "Ошибка данных"], 400);

        if (!$car = FuelCar::find($request->car))
            return response(['message' => "Данные машины не найдены"], 400);

        // Заправки машины
        $fuels = FuelRefueling::where('car', $request->car);

        // Смещение строк для подгрузки данных
        if ($request->offset)
            $fuels = $fuels->offset($request->offset);

        // Получение результата
        $fuels = $fuels->orderBy('mileage', 'DESC')->limit($limit)->get();

        // Обработка данных
        foreach ($fuels as &$fuel) {
            $fuel->date = date("d.m.Y", strtotime($fuel->date));
        }
        
        return response([
            'car' => $car,
            'user' => ($car->user == $request->user()->id),
            'fuels' => $fuels,
            'limit' => $limit,
        ]);

    }

}