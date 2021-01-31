<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class LondonHousingMonthly extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('london_housing_monthly', function ($table){
            $table->bigIncrements('id');
            $table->date('date')->nullable();
            $table->string('area')->nullable(); // Used text because we cant determine length of area string
            $table->integer('average_price')->nullable();
            $table->string('code')->nullable();
            $table->integer('houses_sold')->nullable();
            $table->decimal('no_of_crimes', 8, 2)->nullable();
            $table->boolean('borough_flag')->nullable();
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
        //

        if (Schema::hasTable('london_housing_monthly')){
            Schema::drop('london_housing_monthly');
        }

    }
}
