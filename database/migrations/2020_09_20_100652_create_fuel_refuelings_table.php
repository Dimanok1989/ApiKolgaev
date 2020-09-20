<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFuelRefuelingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fuel_refuelings', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('car')->nullable()->comment('Принадлежность к машине пользователя');
            $table->date('date')->nullable()->comment('Дата заправки');
            $table->bigInteger('mileage')->nullable()->comment('Пробег');
            $table->float('liters')->nullable()->comment('Количество литров');
            $table->float('price')->nullable()->comment('Цена за литр');
            $table->string('type', 250)->nullable()->comment('Вид топлива');
            $table->string('gas_station', 250)->nullable()->comment('Наименование АЗС');
            $table->boolean('full')->default(0)->comment('Полный бак');
            $table->boolean('lost')->default(0)->comment('Забыл записать прошлую заправку');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('fuel_cars', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user')->nullable()->comment('Принадлежность к пользователю');
            $table->string('brand', 250)->nullable()->comment('Марка');
            $table->string('model', 250)->nullable()->comment('Модель');
            $table->string('modification', 250)->nullable()->comment('Модификация');
            $table->bigInteger('year')->nullable()->comment('Год выпуска');
            $table->float('volume')->nullable()->comment('Объём двигателя');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fuel_refuelings');
        Schema::dropIfExists('fuel_cars');
    }
}
