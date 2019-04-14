<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateEventNodeDropsPrimaryKey extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('event_node_drops');

        Schema::create('event_node_drops', function (Blueprint $table) {
            $table->string('event_uid');
            $table->string('event_node_uid');
            $table->string('uid');
            $table->integer('quantity')->unsigned()->nullable();
            $table->integer('count')->unsigned()->nullable();
            $table->decimal('rate', 8, 4)->unsigned()->nullable();
            $table->decimal('apd', 11, 4)->unsigned()->nullable();
            $table->integer('submissions')->unsigned()->nullable();
            $table->integer('sort')->unsigned();
            $table->timestamps();

            $table->primary(['event_uid', 'event_node_uid', 'uid', 'quantity']);
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
