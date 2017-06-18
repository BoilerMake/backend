<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateApplicationNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('application_notes', function (Blueprint $table) {
            $table->increments('id');
            $table->text('message', 65535);
            $table->integer('application_id')->unsigned()->index('application_notes_application_id_foreign');
            $table->integer('user_id')->unsigned()->index('application_notes_user_id_foreign');
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
        Schema::drop('application_notes');
    }
}
