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
     * Добавление новой машины
     * 
     * @param Illuminate\Http\Response $response
     * @return response
     */
    public static function addNewCar(Request $request) {

        $errors = [];

        if (!$request->brand)
            $errors[] = ['name' => "brand", 'message' => "Не указана марка машины"];

        if (!$request->model)
            $errors[] = ['name' => "model", 'message' => "Не указана модель машины"];

        if (count($errors)) {
            return response([
                'message' => "Не заполнены обязательные поля",
                'errors' => $errors
            ], 400);
        }

        $car = FuelCar::create([
            'user' => $request->user()->id,
            'brand' => $request->brand,
            'model' => $request->model,
            'modification' => $request->modification,
            'year' => $request->year,
            'volume' => $request->volume,
        ]);

        return response([
            'car' => $car,
        ]);

    }
    
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
        ->limit(10)->get();

        foreach ($fuels as &$fuel)
            $fuel->date = date("d.m.Y", strtotime($fuel->date));

        return response([
            'cars' => $cars,
            'fuels' => $fuels,
        ]);

    }

    /**
     * Метод вывода заправок по машине
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function getFuelsCar(Request $request) {

        $limit = 30;

        if (!$request->id)
            return response(['message' => "Ошибка данных"], 400);

        if (!$car = FuelCar::find($request->id))
            return response(['message' => "Данные машины не найдены"], 400);

        if ($request->user()->id != $car->user) {

            if (!$request->user()->can('fuel.showAll'))
                return response(['message' => "Доступ ограничен"], 403);

        }

        // Заправки машины
        $fuels = FuelRefueling::where('car', $request->id);

        // Смещение строк для подгрузки данных
        if ($request->offset)
            $fuels = $fuels->offset($request->offset);

        // Получение результата
        $fuels = $fuels->orderBy('mileage', 'DESC')->limit($limit)->get();

        // Обработка данных
        foreach ($fuels as &$fuel) {
            $fuel->date = date("d.m.Y", strtotime($fuel->date));
        }

        $stat = self::getStatisticCar($request->id);
        
        return response([
            'car' => $car,
            'user' => $car->user == $request->user()->id,
            'fuels' => $fuels,
            'limit' => $limit,
            'date' => date("Y-m-d"),
            'statistic' => $stat,
        ]);

    }

    /**
     * Расчет статистики по машине
     */
    public static function getStatisticCar($car) {

        $stantions = []; // Список заправок для автозаполнения

        // Последняя заправка
        $last = FuelRefueling::where('car', $car)->orderBy('id', 'DESC')->get();
        
        // Заполнение последней заправки в список
        if (count($last))
            $stantions[] = $last[0]->gas_station;

        // Самые частопосещаемые заправки
        $favs = FuelRefueling::select(
            \DB::raw('COUNT(*) as count, gas_station')
        )
        ->where('car', $car)
        ->groupBy('gas_station')
        ->orderBy('count', 'DESC')
        ->limit(7)
        ->get();

        foreach ($favs as $row) {

            if (!in_array($row->gas_station, $stantions))
                $stantions[] = $row->gas_station;

        }

        return [
            'stantions' => $stantions,
            'type' => $last[0]->type ?? "",
            'mileage' => $last[0]->mileage ?? null,
        ];

    }

    /**
     * Метод вывода данных для окна добавления новой заправки
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function showAddFuel(Request $request) {

        $data = self::getStatisticCar($request->car);
        $data['date'] = date("Y-m-d");

        return response($data);

    }

    /**
     * Метод записи новой заправки
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function addFuel(Request $request) {

        if (!$car = FuelCar::find($request->car))
            return response(['message' => "Данные машины не найдены"], 400);

        if ($car->user != $request->user()->id)
            return response(['message' => "Доступ к чужим машинам закрыт"], 403);

        $errors = [];

        if (!$request->date)
            $errors[] = ['name' => "date", 'message' => "Не указана дата заправки"];

        if (!$request->mileage)
            $errors[] = ['name' => "mileage", 'message' => "Не указан киллометраж"];

        if (!$request->liters)
            $errors[] = ['name' => "liters", 'message' => "Укажите количество литров"];

        if (!$request->price)
            $errors[] = ['name' => "price", 'message' => "Укажите стоимость одного литра"];

        if (count($errors))
            return response(['message' => "Заполнены не все поля", 'errors' => $errors], 400);

        $refueling = new FuelRefueling;

        $refueling->car = $request->car;
        $refueling->date = $request->date;
        $refueling->mileage = $request->mileage;
        $refueling->liters = $request->liters;
        $refueling->price = $request->price;
        $refueling->type = $request->type;
        $refueling->gas_station = $request->gas_station;
        $refueling->full = (int) $request->full;
        $refueling->lost = (int) $request->lost;

        $refueling->save();

        $refueling->date = date("d.m.Y", strtotime($refueling->date));

        return response([
            'refuel' => $refueling,
        ]);

    }

}