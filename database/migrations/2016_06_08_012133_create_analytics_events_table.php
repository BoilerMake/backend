<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAnalyticsEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->text('params');
            $table->string('client');
            $table->string('referrer');
            $table->string('ip');
            $table->integer('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')->on('users');
            $table->timestamps();
        });
    }

    /**it
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('analytics_events');
    }
}
