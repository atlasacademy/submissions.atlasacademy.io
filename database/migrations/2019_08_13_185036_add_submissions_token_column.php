<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSubmissionsTokenColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->string('receipt', 36)->change();
            $table->string('event_uid', 10)->change();
            $table->string('event_node_uid', 10)->change();
            $table->string('submitter', 50)->change();
            $table->string('token', 50)->nullable()->after("removed");

            $table->index([
                'event_uid',
                'event_node_uid',
                'submitter',
                'token'
            ], 'token_check');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropIndex('token_check');

            $table->dropColumn('token');
        });
    }
}
