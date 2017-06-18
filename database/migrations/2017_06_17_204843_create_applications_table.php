<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateApplicationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('decision')->nullable();
            $table->integer('user_id')->unsigned()->index('applications_user_id_foreign');
            $table->integer('school_id')->unsigned()->nullable()->index('applications_school_id_foreign');
            $table->string('gender')->nullable();
            $table->string('major')->nullable();
            $table->smallInteger('grad_year')->nullable();
            $table->string('diet')->nullable();
            $table->text('diet_restrictions')->nullable();
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
            $table->text('skills')->nullable();
            $table->dateTime('rsvp_deadline')->nullable();
            $table->boolean('has_no_linkedin')->default(0);
            $table->boolean('emailed_decision')->nullable();
            $table->dateTime('checked_in_at')->nullable();
            $table->string('github_etag')->nullable();
            $table->softDeletes();
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
        Schema::drop('applications');
    }
}
