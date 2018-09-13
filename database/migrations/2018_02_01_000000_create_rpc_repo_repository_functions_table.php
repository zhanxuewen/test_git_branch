<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRpcRepoRepositoryFunctionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rpc_repo_repository_functions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('repository_id');
            $table->string('function_name');
            $table->string('modifier');
            $table->string('params')->nullable();
            $table->string('set_model_id');
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
        Schema::dropIfExists('rpc_repo_repository_functions');
    }
}
