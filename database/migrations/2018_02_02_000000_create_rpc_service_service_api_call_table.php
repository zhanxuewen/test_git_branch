<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRpcServiceServiceApiCallTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rpc_service_service_api_call', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('api_id');
            $table->integer('repository_id');
            $table->integer('function_id');
            $table->string('function_name');
            $table->text('params')->nullable();
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
        Schema::dropIfExists('rpc_service_service_api_call');
    }
}
