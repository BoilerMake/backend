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
            $table->integer('user_id')->unsigned()->nullable()->index('github_events_user_id_foreign');
            $table->string('github_event_id');
            $table->string('repo');
            $table->dateTime('timestamp')->nullable();
            $table->text('json', 65535);
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
