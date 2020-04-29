<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogNavicatTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('log_navicat', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->string('project');
            $table->string('connection');
            $table->string('type');
            $table->text('query');
            $table->decimal('time', 15, 8);
            $table->timestamp('created_at');

            $table->index('account_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('log_navicat');
    }
}
