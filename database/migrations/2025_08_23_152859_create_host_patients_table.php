<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('host_patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['active', 'inactive', 'pending', 'completed'])->default('active');
            $table->timestamp('assigned_at')->nullable();
            $table->json('notes')->nullable();
            $table->text('consultation_reason')->nullable();
            $table->timestamps();
            
            // Ensure unique host-patient relationships
            $table->unique(['host_id', 'patient_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('host_patients');
    }
};
