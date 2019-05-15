<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateEventNodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('event_nodes', function (Blueprint $table) {
            $table->integer('cost')->unsigned()->nullable()->after("sheet_name");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('event_nodes', function (Blueprint $table) {
            $table->dropColumn('cost');
        });
    }
}
