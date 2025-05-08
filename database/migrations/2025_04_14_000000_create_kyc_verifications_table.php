<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('kyc_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('session_id')->unique();
            $table->string('status');
            $table->string('document_path')->nullable();
            $table->string('selfie_path')->nullable();
            $table->json('verification_data')->nullable();
            $table->json('liveness_check')->nullable();
            $table->decimal('face_match_score', 5, 2)->nullable();
            $table->string('verification_id')->unique()->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('kyc_verifications');
    }
};