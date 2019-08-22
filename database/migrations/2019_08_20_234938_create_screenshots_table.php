<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateScreenshotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('screenshots', function (Blueprint $table) {
            $table->string('receipt', 36);
            $table->string('event_uid', 10);
            $table->string('event_node_uid', 10);
            $table->string('submitter', 50)->nullable();
            $table->string('filename', 255);
            $table->string('extension', 4);
            $table->text('parse_result')->nullable();
            $table->boolean('parsed')->default(false);
            $table->boolean('submitted')->default(false);
            $table->boolean('removed')->default(false);
            $table->timestamps();
            $table->dateTime('parsed_at')->nullable();

            $table->primary('receipt');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('screenshots');
    }
}
