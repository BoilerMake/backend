<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGithubEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('github_events', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type');
            $table->string('username');
            $table->integer('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')->on('users');
            $table->string('github_event_id');
            $table->string('repo');
            $table->timestamp('timestamp')->nullable()->default(NULL);
            $table->text('json');
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
        Schema::drop('github_events');
    }
}
