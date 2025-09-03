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
        Schema::create('contact_info', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('email')->nullable(); // Contact email
            $table->string('phone')->nullable(); // Contact phone
            $table->text('address')->nullable(); // Contact address
            $table->string('business_hours')->nullable(); // Business hours
            $table->string('support_email')->default('comms@kosmotive.rw'); // Email for form submissions
            $table->json('social_links')->nullable(); // Social media links
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->json('metadata')->nullable(); // Additional contact data
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
        Schema::dropIfExists('contact_info');
    }
};
