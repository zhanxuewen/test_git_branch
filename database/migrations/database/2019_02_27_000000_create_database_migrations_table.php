<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDatabaseMigrationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('database_migrations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('project');
            $table->string('module');
            $table->string('migration_name');
            $table->string('table_name');
            $table->string('migrate_type');
            $table->string('engine');
            $table->string('id_type');
            $table->text('columns');
            $table->string('index');
            $table->boolean('timestamps');
            $table->boolean('has_deleted');
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
        //
    }
}
