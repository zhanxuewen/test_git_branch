<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMonitorOrderIncrementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('monitor_order_increment', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type');
            $table->unsignedBigInteger('count');
            $table->date('created_date');

            $table->index('type');
            $table->index('created_date');
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
    }
}
