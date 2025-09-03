<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('consultation_bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('reason_key');
            $table->timestamp('preferred_datetime');
            $table->unsignedInteger('duration_minutes');
            $table->text('notes')->nullable();
            $table->string('status')->default('pending'); // pending, assigned, scheduled, completed, cancelled
            $table->unsignedBigInteger('assigned_host_id')->nullable();
            $table->unsignedBigInteger('meeting_id')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('assigned_host_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_bookings');
    }
};
