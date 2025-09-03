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
        Schema::create('user_stories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('content');
            $table->string('category')->nullable(); // e.g., 'period', 'pregnancy', 'general'
            $table->json('tags')->nullable(); // Array of tags for categorization
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'archived'])->default('draft');
            $table->text('admin_notes')->nullable(); // Private notes from admin
            $table->timestamp('submitted_at')->nullable(); // When user submitted for review
            $table->timestamp('approved_at')->nullable(); // When admin approved
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->json('metadata')->nullable(); // Additional data (age range, location, etc.)
            $table->boolean('is_featured')->default(false); // Featured stories
            $table->integer('sort_order')->default(0); // For ordering stories
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['status', 'approved_at']);
            $table->index(['category', 'status']);
            $table->index(['is_featured', 'approved_at']);
            $table->index('submitted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_stories');
    }
};
