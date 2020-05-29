<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiskFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('disk_files', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user')->nullable()->comment('Принадлежность к пользователю');
            $table->string('path', 250)->nullable()->comment('Путь до файла');
            $table->string('real_name', 150)->nullable()->comment('Настоящее имя файла');
            $table->string('name', 150)->nullable()->comment('Имя файла');
            $table->bigInteger('size')->default(0)->comment('Размер файла в байтах');
            $table->string('ext', 20)->nullable()->comment('Расширение файла');
            $table->string('mime_type', 200)->nullable()->comment('MimeType');
            $table->boolean('is_dir')->default(0)->comment('Этот файл - каталог');
            $table->bigInteger('in_dir')->default(0)->comment('Принадлежность к каталогу. 0 - корневой каталог пользователя');
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
        Schema::dropIfExists('disk_files');
    }
}
