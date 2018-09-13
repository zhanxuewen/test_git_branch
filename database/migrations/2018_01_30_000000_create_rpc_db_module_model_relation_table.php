<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRpcDbModuleModelRelationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rpc_db_module_model_relation', function (Blueprint $table) {
            $table->increments('id');
            $table->string('relation');
            $table->integer('model_id');
            $table->string('relate_type');
            $table->string('related_model');
            $table->integer('related_model_id');
            $table->string('foreign_key')->nullable();
            $table->string('local_key')->nullable();
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
        Schema::dropIfExists('rpc_db_module_model_relation');
    }
}
