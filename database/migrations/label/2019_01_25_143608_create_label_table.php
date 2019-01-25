<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLabelTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('label', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name');
            $table->string('code');
            $table->boolean('is_active');
            $table->integer('label_type_id');
            $table->integer('parent_id');
            $table->unsignedTinyInteger('level');
            $table->decimal('power', 15, 8);
            $table->softDeletes();
            $table->timestamps();

            $table->index('parent_id');
            $table->index('label_type_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('label');
    }
}
