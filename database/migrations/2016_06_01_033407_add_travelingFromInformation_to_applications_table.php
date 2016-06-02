<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTravelingFromInformationToApplicationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('travellingFrom')->nullable();
            $table->boolean('isTravellingFromSchool')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return voidr
     */
    public function down()
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn('travellingFrom');
            $table->dropColumn('isTravellingFromSchool');
        });
    }
}
