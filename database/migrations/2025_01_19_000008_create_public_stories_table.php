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
        Schema::create('public_stories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('original_story_uuid'); // Reference to user_stories.uuid (for admin tracking only)
            $table->string('title');
            $table->text('content');
            $table->string('category')->nullable();
            $table->json('tags')->nullable();
            $table->string('anonymous_author')->default('Anonymous'); // Always anonymous
            $table->json('author_metadata')->nullable(); // Anonymous demographic info (age range, etc.)
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->integer('views_count')->default(0); // Track story views
            $table->integer('likes_count')->default(0); // Track story likes
            $table->timestamp('published_at'); // When story was made public
            $table->foreignId('published_by')->constrained('users')->onDelete('cascade'); // Admin who published
            $table->json('metadata')->nullable(); // Additional public metadata
            $table->timestamps();
            
            $table->index(['category', 'published_at']);
            $table->index(['is_featured', 'published_at']);
            $table->index(['published_at', 'sort_order']);
            $table->index('views_count');
            $table->index('likes_count');
            $table->index('original_story_uuid'); // For admin reference only
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_stories');
    }
};
