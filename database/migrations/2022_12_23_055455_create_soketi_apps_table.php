<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('soketi_apps', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('key');
            $table->string('secret');
            $table->integer('max_connections');
            $table->boolean('enable_client_messages');
            $table->tinyInteger('enabled');
            $table->integer('max_backend_events_per_sec');
            $table->integer('max_client_events_per_sec');
            $table->integer('max_read_req_per_sec');
            $table->json('webhooks')->nullable();
            $table->tinyInteger('max_presence_members_per_channel')->nullable();
            $table->tinyInteger('max_presence_member_size_in_kb')->nullable();
            $table->tinyInteger('max_channel_name_length')->nullable();
            $table->tinyInteger('max_event_channels_at_once')->nullable();
            $table->tinyInteger('max_event_name_length')->nullable();
            $table->tinyInteger('max_event_payload_in_kb')->nullable();
            $table->tinyInteger('max_event_batch_size')->nullable();
            $table->boolean('enable_user_authentication');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('soketi_apps');
    }
};
