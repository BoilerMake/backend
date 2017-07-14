<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSubtitleToUserStatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_stats', function (Blueprint $table) {
            $table->string('subtitle')->nullable()->after('event');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_stats', function (Blueprint $table) {
            $table->dropColumn('subtitle');
        });
    }
}
