<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRpcServiceServiceApiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rpc_service_service_api', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('service_id');
            $table->string('function_name');
            $table->string('modifier');
            $table->text('params')->nullable();
            $table->boolean('has_transaction');
            $table->string('return')->nullable();
            $table->string('author')->nullable();
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
        Schema::dropIfExists('rpc_service_service_api');
    }
}
