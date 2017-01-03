<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateApplicationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn('essay1');
            $table->dropColumn('essay2');
            $table->dropColumn('tshirt');
            $table->dropColumn('travellingFrom');
            $table->dropColumn('isTravellingFromSchool');
            $table->dropColumn('age');

            $table->boolean('needsTravelReimbursement')->nullable();
            $table->boolean('isFirstHackathon')->nullable();
            $table->string('race')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->text('essay1')->nullable();
            $table->text('essay2')->nullable();
            $table->string('tshirt')->nullable();
            $table->string('travellingFrom')->nullable();
            $table->boolean('isTravellingFromSchool')->default(true);
            $table->tinyInteger('age')->unsigned()->nullable();

            $table->dropColumn('needsTravelReimbursement');
            $table->dropColumn('isFirstHackathon');
            $table->dropColumn('race');
        });
    }
}
