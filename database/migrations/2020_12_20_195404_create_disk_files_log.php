<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiskFilesLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('disk_files_logs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable()->comment('Идентификатор пользователя');
            $table->bigInteger('file_id')->nullable()->comment('Идентификатор файла');
            $table->string('type', 50)->nullable()->comment('Тип операции');
            $table->string('operation_id', 50)->nullable()->comment('Идентификатор операции с файлами');
            $table->string('comment', 250)->nullable()->comment('Приоритетное описание операции');
            $table->timestamps();
        });
        Schema::table('disk_files', function (Blueprint $table) {
            $table->string('operation_id', 50)->nullable()->comment('Идентификатор операции с файлами');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('disk_files_logs');
        Schema::dropIfExists('disk_files');
    }
}
