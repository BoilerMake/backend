<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEventForeignsToPodScansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pod_scans', function (Blueprint $table) {
            $table->integer('event_id')->unsigned()->nullable();
            $table->foreign('event_id')->references('id')->on('events');

            $table->dropForeign('pod_scans_pod_event_id_foreign');
            $table->dropColumn('pod_event_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pod_scans', function (Blueprint $table) {
            $table->dropForeign('pod_scans_event_id_foreign');
            $table->dropColumn('event_id');
        });
    }
}
