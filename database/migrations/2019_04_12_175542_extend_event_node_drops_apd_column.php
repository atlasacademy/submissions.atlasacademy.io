<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ExtendEventNodeDropsApdColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('event_node_drops', function (Blueprint $table) {
            $table->decimal('apd', 11, 4)->unsigned()->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('event_node_drops', function (Blueprint $table) {
            $table->decimal('apd', 8, 4)->unsigned()->nullable()->change();
        });
    }
}
