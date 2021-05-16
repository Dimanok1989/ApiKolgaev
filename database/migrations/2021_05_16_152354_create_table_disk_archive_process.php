<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableDiskArchiveProcess extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('disk_process_archives', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable()->comment('Принадлежность к пользователю');
            $table->string('uid', 100)->nullable()->comment("Идентификатор операции");
            $table->string('name', 100)->nullable()->comment("Имя файла архива");
            $table->timestamps();
            $table->dateTime('created_done')->nullable()->comment("Время заершения создания архива");
            $table->dateTime('downloaded')->nullable()->comment("Время скачивания");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('disk_process_archives');
    }
}
