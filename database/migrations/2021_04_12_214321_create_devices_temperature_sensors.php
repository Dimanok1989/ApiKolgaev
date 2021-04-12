<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDevicesTemperatureSensors extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('devices_temperature_sensors', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable()->comment('Идентификатор пользователя');
            $table->string('sensor_api', 50)->nullable()->comment('Ключ датчика');
            $table->string('sensor_name', 50)->nullable()->comment('Имя датчика');
            $table->string('sensor_mac', 50)->nullable()->comment('Мак-адрес датчика');
            $table->timestamps();
        });

        Schema::create('devices_temperature_data_sensors', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('sensor_id')->nullable()->comment('Идентификатор датчика');
            $table->float('temperature')->nullable()->comment('Температура');
            $table->timestamp('created_at')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('devices_temperature_sensors');
        Schema::dropIfExists('devices_temperature_data_sensors');
    }
}
