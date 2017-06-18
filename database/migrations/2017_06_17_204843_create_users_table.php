<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUsersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('users', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('first_name')->nullable();
			$table->string('last_name')->nullable();
			$table->string('email')->unique();
			$table->string('password', 60);
			$table->string('phone')->nullable();
			$table->timestamps();
			$table->string('identifier')->nullable()->unique();
			$table->boolean('confirmed')->default(0);
			$table->string('confirmation_code')->nullable();
			$table->string('card_code')->nullable();
			$table->string('card_image')->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('users');
	}

}
