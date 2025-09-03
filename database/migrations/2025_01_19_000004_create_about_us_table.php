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
        Schema::create('about_us', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->text('mission');
            $table->text('vision');
            $table->text('values')->nullable(); // Optional company values
            $table->text('story')->nullable(); // Optional company story
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->json('metadata')->nullable(); // For additional data like founding date, location, etc.
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('about_us');
    }
};
