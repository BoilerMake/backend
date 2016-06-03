<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEventForeignsToPodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pods', function (Blueprint $table) {
            $table->integer('current_event_id')->unsigned()->nullable();
            $table->foreign('current_event_id')->references('id')->on('events');


            $table->dropForeign('pods_current_pod_event_id_foreign');
            $table->dropColumn('current_pod_event_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pods', function (Blueprint $table) {
            $table->dropForeign('pods_current_event_id_foreign');
            $table->dropColumn('current_event_id');
        });
    }
}
