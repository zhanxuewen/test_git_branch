<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNoticeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notice', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('label_id');
            $table->integer('sender_id');
            $table->integer('receiver_id');
            $table->text('content');
            $table->boolean('is_system');
            $table->boolean('status');
            $table->boolean('is_visible');
            $table->boolean('has_read');
            $table->timestamps();

            $table->index('label_id');
            $table->index('sender_id');
            $table->index('receiver_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notice');
    }
}
