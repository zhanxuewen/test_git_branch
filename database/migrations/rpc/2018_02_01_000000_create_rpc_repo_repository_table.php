<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRpcRepoRepositoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rpc_repo_repository', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code');
            $table->integer('module_id');
            $table->string('class_name');
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
        Schema::dropIfExists('rpc_repo_repository');
    }
}
