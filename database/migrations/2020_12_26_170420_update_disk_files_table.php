<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateDiskFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('disk_files', function (Blueprint $table) {
            $table->bigInteger('downloads')->default(0)->comment('Количество скачиваний');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('disk_files', function (Blueprint $table) {
            $table->dropColumn('downloads');
        });
    }
}
