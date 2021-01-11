<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('user_id')->unsigned();            
            $table->string('assignee');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['assigned', 'in-progress', 'completed']);
            $table->date('due_date')->nullable();
            $table->date('completed_at')->nullable();
            $table->softDeletes();

            $table->timestamps();
        });

        Schema::table('tasks', function($table) {
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tasks');
    }
}
