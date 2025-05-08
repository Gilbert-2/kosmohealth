<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePollResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('poll_results', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');

            $table->bigInteger('poll_id')->unsigned()->nullable();
            $table->foreign('poll_id')->references('id')->on('polls')->onDelete('cascade');
            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            $table->string('answer')->nullable();

            $table->json('meta')->nullable();
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
        Schema::table('poll_results', function (Blueprint $table) {
            $table->dropForeign('poll_results_poll_id_foreign');
            $table->dropForeign('poll_results_user_id_foreign');
        });

        Schema::dropIfExists('poll_results');
    }
}
