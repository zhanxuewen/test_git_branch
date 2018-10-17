<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMonitorTableIncrementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('monitor_table_increment', function (Blueprint $table) {
            $table->increments('id');
            $table->string('table');
            $table->unsignedBigInteger('rows');
            $table->unsignedBigInteger('auto_increment_id');
            $table->unsignedTinyInteger('level');
            $table->date('created_date');
            
            $table->index('table');
            $table->index('level');
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
