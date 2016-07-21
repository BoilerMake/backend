<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropPodEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pod_events', function (Blueprint $table) {
            Schema::drop('pod_events');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pod_events', function (Blueprint $table) {
            $table->increments('id');
            $table->string('event_name');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }
}
