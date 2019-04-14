<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubmissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->string('receipt');
            $table->string('event_uid');
            $table->string('event_node_uid');
            $table->string('submitter')->nullable();
            $table->text('drops');
            $table->boolean('uploaded');
            $table->boolean('removed');
            $table->timestamps();

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
        Schema::dropIfExists('submissions');
    }
}
