<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropGroupMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::drop('group_messages');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('group_messages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('group');
            $table->string('message');
            $table->integer('num_recipients');
            $table->timestamps();
        });
    }
}
