<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTermsandconditionsToApplicationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->boolean('tandc_1')->default(false);
            $table->boolean('tandc_2')->default(false);
            $table->boolean('tandc_3')->default(false);
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
            $table->dropColumn('tandc_1');
            $table->dropColumn('tandc_2');
            $table->dropColumn('tandc_3');
        });
    }
}
