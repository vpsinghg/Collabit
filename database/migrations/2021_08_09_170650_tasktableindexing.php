<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class Tasktableindexing extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('tasks',  function    (Blueprint $table){
            $table->index(['created_at','user_id']);
            $table->index([DB::raw('title(256)')]);
            $table->index([DB::raw('description(728)')]);
            $table->index('assignee');
            $table->index('dueDate');
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
