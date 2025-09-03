<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('story_interactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('public_story_id')->constrained('public_stories')->onDelete('cascade');
            $table->enum('interaction_type', ['view', 'like', 'unlike', 'report'])->default('view');
            $table->string('ip_address')->nullable(); // For analytics and abuse prevention
            $table->json('metadata')->nullable(); // Additional interaction data
            $table->timestamps();
            
            $table->unique(['user_id', 'public_story_id', 'interaction_type'], 'unique_user_story_interaction');
            $table->index(['public_story_id', 'interaction_type']);
            $table->index(['user_id', 'interaction_type']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('story_interactions');
    }
};
