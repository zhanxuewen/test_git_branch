<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRpcDbModuleModelTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rpc_db_module_model', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('module_id');
            $table->string('code');
            $table->string('alias');  // in _models.php
            $table->string('class_name');
            $table->string('label')->nullable();
            $table->string('table');
            $table->text('fillable');
            $table->boolean('timestamps');
            $table->boolean('use_soft_deletes');
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
        Schema::dropIfExists('rpc_db_module_model');
    }
}
