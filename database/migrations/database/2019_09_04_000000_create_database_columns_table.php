<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDatabaseColumnsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('database_columns', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('group_id');
            $table->string('column');
            $table->string('type');
            $table->tinyInteger('order');
            $table->string('info')->nullable();
            $table->softDeletes();

            $table->index('group_id');
        });
    }

}
