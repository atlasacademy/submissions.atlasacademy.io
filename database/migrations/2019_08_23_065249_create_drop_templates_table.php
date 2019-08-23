<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDropTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('drop_templates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('drop_uid');
            $table->integer('quantity')->unsigned()->nullable();
            $table->integer('bonus')->unsigned()->nullable();
            $table->longText('image');
            $table->timestamps();

            $table->unique(["drop_uid", "quantity", "bonus"]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('drop_templates');
    }
}
