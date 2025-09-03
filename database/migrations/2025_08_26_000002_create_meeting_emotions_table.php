<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('meeting_emotions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('meeting_id');
            $table->unsignedBigInteger('host_id');
            $table->unsignedBigInteger('patient_id');
            $table->json('timeline')->nullable(); // [{t: ISO, emotions: {happy:0.3, sad:0.1, ...}}]
            $table->json('summary')->nullable();  // {dominant:"happy", averages:{}, counts:{}}
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index('meeting_id');
            $table->index(['host_id', 'patient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_emotions');
    }
};
