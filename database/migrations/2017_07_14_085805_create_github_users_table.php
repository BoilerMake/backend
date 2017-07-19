<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGithubUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('github_users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('username');
            $table->string('access_token')->nullable();
            $table->integer('github_user_id')->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('gravatar_id')->nullable();
            $table->string('type')->nullable();
            $table->string('name')->nullable();
            $table->string('company')->nullable();
            $table->string('blog')->nullable();
            $table->string('location')->nullable();
            $table->string('email')->nullable();
            $table->text('bio')->nullable();
            $table->integer('public_repos')->default(0);
            $table->integer('public_gists')->default(0);
            $table->integer('followers')->default(0);
            $table->integer('following')->default(0);
            $table->timestamp('github_account_created_at')->nullable();
            $table->timestamp('last_fetched_at')->nullable();
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
        Schema::drop('github_users');
    }
}
