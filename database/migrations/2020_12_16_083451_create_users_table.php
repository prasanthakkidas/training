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
            $table->increments('id');

            
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('name');
            $table->string('email')->unique()->notNullable();
            $table->string('password');
            $table->boolean('is_admin')->default($value = false);
            $table->string('created_by')->nullable()->default($value = NULL);
            $table->string('deleted_by')->nullable()->default($value = NULL);
            $table->softDeletes();
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
