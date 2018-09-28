<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeToMultipleTeamNames extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('team_names');
            $table->string('team_name_1')->nullable();
            $table->string('team_name_2')->nullable();
            $table->string('team_name_3')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('team_names')->nullable();
            $table->dropColumn('team_name_1');
            $table->dropColumn('team_name_2');
            $table->dropColumn('team_name_3');
        });
    }
}
