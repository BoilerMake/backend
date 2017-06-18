<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateApplicationsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('applications', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('decision')->nullable();
			$table->integer('user_id')->unsigned()->index('applications_user_id_foreign');
			$table->integer('school_id')->unsigned()->nullable()->index('applications_school_id_foreign');
			$table->string('gender')->nullable();
			$table->string('major')->nullable();
			$table->smallInteger('grad_year')->nullable();
			$table->string('diet')->nullable();
			$table->text('diet_restrictions', 65535)->nullable();
			$table->softDeletes();
			$table->timestamps();
			$table->string('github')->nullable();
			$table->string('linkedin')->nullable();
			$table->string('resume_filename')->nullable();
			$table->boolean('resume_uploaded')->default(0);
			$table->boolean('rsvp')->nullable();
			$table->boolean('needsTravelReimbursement')->nullable();
			$table->boolean('isFirstHackathon')->nullable();
			$table->string('race')->nullable();
			$table->boolean('has_no_github')->default(0);
			$table->boolean('completed_calculated')->default(0);
			$table->text('skills', 65535);
			$table->dateTime('rsvp_deadline')->nullable();
			$table->boolean('has_no_linkedin')->default(0);
			$table->boolean('emailed_decision')->nullable();
			$table->dateTime('checked_in_at')->nullable();
			$table->string('github_etag')->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('applications');
	}

}
