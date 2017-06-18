<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePodScansTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('pod_scans', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('pod_id')->unsigned()->index('pod_scans_pod_id_foreign');
			$table->string('input');
			$table->integer('user_id')->unsigned()->nullable()->index('pod_scans_user_id_foreign');
			$table->boolean('success');
			$table->string('message');
			$table->timestamps();
			$table->integer('event_id')->unsigned()->nullable()->index('pod_scans_event_id_foreign');
			$table->string('ip');
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
