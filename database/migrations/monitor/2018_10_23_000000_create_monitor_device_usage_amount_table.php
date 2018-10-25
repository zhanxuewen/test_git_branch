<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMonitorDeviceUsageAmountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('monitor_device_usage_amount', function (Blueprint $table) {
            $table->increments('id');
            $table->string('device');
            $table->unsignedInteger('user_amount');
            $table->date('created_date');
            
            $table->index('device');
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
