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
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('email');
            $table->text('message');
            $table->string('subject')->nullable(); // Optional subject field
            $table->string('phone')->nullable(); // Optional phone field
            $table->enum('status', ['new', 'read', 'replied', 'archived'])->default('new');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->string('ip_address')->nullable(); // For security tracking
            $table->string('user_agent')->nullable(); // For security tracking
            $table->timestamp('email_sent_at')->nullable(); // Track when email was sent
            $table->json('email_response')->nullable(); // Store email service response
            $table->json('metadata')->nullable(); // Additional data
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null'); // Admin assignment
            $table->text('admin_notes')->nullable(); // Internal notes
            $table->timestamps();
            
            $table->index(['status', 'created_at']);
            $table->index(['priority', 'created_at']);
            $table->index('email');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
