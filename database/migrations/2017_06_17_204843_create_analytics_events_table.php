<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAnalyticsEventsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('analytics_events', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('name');
			$table->text('params', 65535);
			$table->string('client');
			$table->string('referrer');
			$table->string('ip');
			$table->integer('user_id')->unsigned()->nullable()->index('analytics_events_user_id_foreign');
			$table->timestamps();
			$table->string('url');
			$table->string('ua');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('analytics_events');
	}

}
