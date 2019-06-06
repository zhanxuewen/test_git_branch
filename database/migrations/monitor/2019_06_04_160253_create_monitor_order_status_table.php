<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMonitorOrderStatusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('monitor_order_status', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type');
            $table->integer('origin_id');
            $table->integer('student_id');
            $table->integer('school_id')->nullable();
            $table->boolean('is_group')->default(0);
            $table->integer('commodity_id')->nullable();
            $table->integer('days')->nullable();
            $table->decimal('pay_fee', 8, 2);
            $table->boolean('is_refunded');
            $table->decimal('refund_fee', 8, 2);
            $table->decimal('remained_fee', 8, 2)->nullable();
            $table->date('finished_date');
            $table->date('refunded_date');
            $table->softDeletes();

            $table->index('origin_id');
            $table->index('student_id');
            $table->index('school_id');
            $table->index('finished_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('monitor_order_status');
    }
}
