<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveDiskThumbsTablePahtColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('disk_files_thumbnails', function (Blueprint $table) {
            $table->dropColumn('paht');
            $table->string('litle_path', 150)->comment('Путь до каталога с эскизом')->after('litle');
            $table->string('middle_path', 150)->comment('Путь до каталога с урезанной копией')->after('middle');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('disk_files_thumbnails', function (Blueprint $table) {
            $table->string('paht', 150)->comment('Путь до каталога с эскизом')->after('file_id');
            $table->dropColumn('litle_path');
            $table->dropColumn('middle_path');
        });
    }
}
