<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->string('uid');
            $table->string('sheet_type');
            $table->string('sheet_id');
            $table->string('name');
            $table->string('node_filter')->nullable();
            $table->integer('sort')->unsigned();
            $table->boolean('active');
            $table->boolean('submittable');
            $table->timestamps();

            $table->primary('uid');
        });

        Schema::create('event_nodes', function (Blueprint $table) {
            $table->string('event_uid');
            $table->string('uid');
            $table->string('name');
            $table->string('sheet_name');
            $table->integer('submissions')->unsigned();
            $table->integer('submitters')->unsigned();
            $table->integer('sort')->unsigned();
            $table->boolean('active');
            $table->timestamps();

            $table->primary(['event_uid', 'uid']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('event_nodes');
        Schema::dropIfExists('events');
    }
}
