<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            // Channels per notification type
            $table->boolean('period_reminders')->default(true);
            $table->boolean('ovulation_alerts')->default(true);
            $table->boolean('appointment_reminders')->default(true);
            $table->boolean('community_updates')->default(true);
            // Admin/host ops (optional)
            $table->boolean('admin_announcements')->default(true);
            $table->boolean('system_alerts')->default(true);
            // Quiet hours (optional)
            $table->time('quiet_from')->nullable();
            $table->time('quiet_to')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};


