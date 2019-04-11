<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDropsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('drops', function (Blueprint $table) {
            $table->string('uid');
            $table->string('name');
            $table->string('type');
            $table->integer('quantity')->unsigned()->nullable();
            $table->string('image')->nullable();
            $table->string('image_original')->nullable();
            $table->integer('sort')->unsigned();
            $table->boolean('event');
            $table->boolean('active');
            $table->timestamps();

            $table->primary('uid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('drops');
    }
}
