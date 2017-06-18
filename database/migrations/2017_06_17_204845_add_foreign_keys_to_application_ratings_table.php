<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToApplicationRatingsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('application_ratings', function(Blueprint $table)
		{
			$table->foreign('application_id')->references('id')->on('applications')->onUpdate('RESTRICT')->onDelete('RESTRICT');
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
		Schema::table('application_ratings', function(Blueprint $table)
		{
			$table->dropForeign('application_ratings_application_id_foreign');
			$table->dropForeign('application_ratings_user_id_foreign');
		});
	}

}
