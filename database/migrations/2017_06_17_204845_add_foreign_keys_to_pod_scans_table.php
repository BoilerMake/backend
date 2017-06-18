<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToPodScansTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('pod_scans', function(Blueprint $table)
		{
			$table->foreign('event_id')->references('id')->on('events')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('pod_id')->references('id')->on('pods')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('user_id')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('pod_scans', function(Blueprint $table)
		{
			$table->dropForeign('pod_scans_event_id_foreign');
			$table->dropForeign('pod_scans_pod_id_foreign');
			$table->dropForeign('pod_scans_user_id_foreign');
		});
	}

}
