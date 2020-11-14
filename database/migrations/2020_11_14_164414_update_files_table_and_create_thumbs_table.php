<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateFilesTableAndCreateThumbsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('disk_files', function (Blueprint $table) {
            $table->timestamp('thumbnail_created')->nullable()->comment('Дата создания миниатюры');
        });

        Schema::create('disk_files_thumbnails', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('file_id');
            $table->string('paht', 150)->comment('Путь до каталога с эскизом');
            $table->string('litle', 50)->comment('Миниатюра для иконки');
            $table->bigInteger('litle_size')->default(0)->comment('Размер миниатюры в байтах');
            $table->string('middle', 50)->comment('Файл для просмотра на сайте');
            $table->bigInteger('middle_size')->default(0)->comment('Размер файла в байтах');
            $table->timestamps();
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
        Schema::dropIfExists('disk_files_thumbnails');
    }
}
