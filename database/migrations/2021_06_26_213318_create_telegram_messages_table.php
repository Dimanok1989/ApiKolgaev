<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTelegramMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('telegram_incoming_messages', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("message_id")->nullable()->comment("Порядковый номер сообщения бота");
            $table->string("chat_id")->nullable()->comment("Идентификтаор чат-группы");
            $table->text('message')->nullable()->comment("Текст сообщения");
            $table->text('request')->nullable()->comment("JSON входящего запроса");
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
        Schema::dropIfExists('telegram_incoming_messages');
    }
}
