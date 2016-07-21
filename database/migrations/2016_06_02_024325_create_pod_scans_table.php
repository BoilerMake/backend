<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePodScansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pod_scans', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('pod_id')->unsigned();
            $table->foreign('pod_id')->references('id')->on('pods');
            $table->integer('pod_event_id')->unsigned()->nullable();
            $table->foreign('pod_event_id')->references('id')->on('events');
            $table->string('input');
            $table->integer('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')->on('users');
            $table->boolean('success');
            $table->string('message');
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
        Schema::drop('pod_scans');
    }
}
