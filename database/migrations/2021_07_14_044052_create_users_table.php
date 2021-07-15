<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->notNullable();
            $table->string('email')->unique()->notNullable();
            $table->string('password')->nullable();
            $table->boolean('isVerified')->default(0);
            $table->bigInteger('createdBy')->nullable();
            $table->bigInteger('deletedBy')->nullable();
            $table->string('role')->default('normal');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
