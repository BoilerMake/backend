<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateInboundMessagesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('inbound_messages', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('user_id')->unsigned()->nullable()->index('inbound_messages_user_id_foreign');
			$table->string('number')->nullable();
			$table->string('message')->nullable();
			$table->text('raw', 65535)->nullable();
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
		Schema::drop('inbound_messages');
	}

}
