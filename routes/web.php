<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('spa');
    return view('welcome');
});

Route::get('/react', function () {

    return view('react');

    return response([]);

    // $user = App\User::find(1);
    // dd($user->isAdmin);
    // dd($user->hasRole('web-developer')); // вернёт true
    // dd($user->hasRole('project-manager'));// вернёт false
    // dd($user->givePermissionsTo('manage-users'));
    // dd($user->hasPermissionTo('manage-users'));// вернёт true
    $File = "H:/fuels.xlsx";
    $excel = \PHPExcel_IOFactory::load($File);

    $lists = [];

    foreach($excel->getWorksheetIterator() as $worksheet) {
        $lists[] = $worksheet->toArray();
    }

    $data = [];

    $rows = 0;
    foreach ($lists as $list) {
        // Перебор строк
        foreach ($list as $row) {
            // Перебор столбцов
            foreach ($row as $col) {
                $data[$rows][] = $col;
            }
            $rows++;
        }
    }

    $resp = [];
    foreach ($data as $row) {

        if (!$row[0])
            continue;

        $time = strtotime($row[0]);

        $full = $lost = 0;

        if ($row[6] == 1)
            $full = 1;
        elseif ($row[6] == 2)
            $full = $lost = 1;

        $resp[] = [
            'car' => 1,
            'date' => date("Y-m-d", $time),
            'mileage' => $row[1],
            'liters' => (float) $row[2],
            'price' => (float) $row[3],
            'type' => $row[5],
            'full' => $full,
            'lost' => $lost,
            'gas_station' => $row[7],
            // 'row' => $row,
        ];

    }

    usort($resp, function($a, $b){
        return ($a['mileage'] - $b['mileage']);
    });

    foreach ($resp as &$row) {

        $FuelRefueling = new \App\FuelRefueling;

        foreach ($row as $key => $value) {
            $FuelRefueling->$key = $value;
        }
        // $FuelRefueling->save();

        $row['model'] = $FuelRefueling;
    
    }

    return response($resp);

});

Route::get('/{any}', function () {
    return view('spa');
});
Route::get('/{any}/{param}', function () {
    return view('spa');
});

// Auth::routes();