<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserRolesUserPermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_roles_user_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('user_role_id');
            $table->unsignedBigInteger('user_permission_id');
            $table->foreign('user_role_id')->references('id')->on('user_roles')->onDelete('cascade');
            $table->foreign('user_permission_id')->references('id')->on('user_permissions')->onDelete('cascade');
            $table->primary(['user_role_id','user_permission_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_roles_user_permissions');
    }
}
