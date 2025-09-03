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
        // Pregnancy Records Table
        Schema::create('pregnancy_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('lmp_date'); // Last Menstrual Period date
            $table->date('conception_date')->nullable(); // Calculated conception date
            $table->date('due_date'); // Calculated due date
            $table->date('ultrasound_date')->nullable(); // Ultrasound confirmed date
            $table->date('ultrasound_due_date')->nullable(); // Ultrasound due date
            $table->integer('gestational_age_weeks')->default(0); // Current gestational age in weeks
            $table->integer('gestational_age_days')->default(0); // Current gestational age in days
            $table->enum('trimester', ['1st', '2nd', '3rd'])->default('1st');
            $table->enum('status', ['active', 'completed', 'miscarriage', 'terminated'])->default('active');
            $table->json('medical_history')->nullable(); // Pre-existing conditions, medications
            $table->json('pregnancy_complications')->nullable(); // Any complications
            $table->decimal('pre_pregnancy_weight', 5, 2)->nullable(); // Weight before pregnancy
            $table->decimal('current_weight', 5, 2)->nullable(); // Current weight
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'status']);
            $table->index('due_date');
        });

        // Pregnancy Symptoms Table
        Schema::create('pregnancy_symptoms', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('pregnancy_record_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('symptom_date');
            $table->string('symptom_type'); // nausea, fatigue, back_pain, etc.
            $table->enum('severity', ['mild', 'moderate', 'severe'])->default('mild');
            $table->text('description')->nullable();
            $table->json('symptom_data')->nullable(); // Additional symptom-specific data
            $table->boolean('requires_medical_attention')->default(false);
            $table->text('medical_notes')->nullable();
            $table->timestamps();
            
            $table->index(['pregnancy_record_id', 'symptom_date'], 'preg_symptom_record_date_idx');
            $table->index(['user_id', 'symptom_date'], 'preg_symptom_user_date_idx');
            $table->index('symptom_type', 'preg_symptom_type_idx');
        });

        // Pregnancy Appointments Table
        Schema::create('pregnancy_appointments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('pregnancy_record_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('appointment_type'); // prenatal, ultrasound, lab_work, etc.
            $table->string('doctor_name')->nullable();
            $table->string('clinic_name')->nullable();
            $table->string('clinic_address')->nullable();
            $table->string('clinic_phone')->nullable();
            $table->dateTime('appointment_date');
            $table->enum('status', ['scheduled', 'completed', 'cancelled', 'rescheduled'])->default('scheduled');
            $table->text('notes')->nullable();
            $table->text('results')->nullable(); // Appointment results/outcomes
            $table->json('appointment_data')->nullable(); // Additional appointment data
            $table->boolean('reminder_sent')->default(false);
            $table->timestamps();
            
            $table->index(['pregnancy_record_id', 'appointment_date'], 'preg_appt_record_date_idx');
            $table->index(['user_id', 'appointment_date'], 'preg_appt_user_date_idx');
            $table->index('appointment_type', 'preg_appt_type_idx');
            $table->index('status', 'preg_appt_status_idx');
        });

        // Baby Development Milestones Table
        Schema::create('baby_development_milestones', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('pregnancy_record_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('week_number'); // Gestational week
            $table->string('milestone_title');
            $table->text('milestone_description');
            $table->string('baby_size')->nullable(); // e.g., "size of a lemon"
            $table->decimal('baby_length_cm', 5, 2)->nullable(); // Length in cm
            $table->decimal('baby_weight_grams', 8, 2)->nullable(); // Weight in grams
            $table->json('development_details')->nullable(); // Detailed development info
            $table->json('organ_development')->nullable(); // Organ development status
            $table->json('movement_patterns')->nullable(); // Expected movement patterns
            $table->boolean('is_completed')->default(false);
            $table->date('completed_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['pregnancy_record_id', 'week_number'], 'preg_milestone_record_week_idx');
            $table->index(['user_id', 'week_number'], 'preg_milestone_user_week_idx');
            $table->unique(['pregnancy_record_id', 'week_number', 'milestone_title'], 'preg_milestone_unique_idx');
        });

        // Pregnancy Health Metrics Table
        Schema::create('pregnancy_health_metrics', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('pregnancy_record_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('measurement_date');
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->decimal('blood_pressure_systolic', 5, 2)->nullable();
            $table->decimal('blood_pressure_diastolic', 5, 2)->nullable();
            $table->decimal('blood_sugar', 5, 2)->nullable();
            $table->decimal('fundal_height_cm', 5, 2)->nullable(); // Fundal height measurement
            $table->decimal('bmi', 5, 2)->nullable();
            $table->json('vital_signs')->nullable(); // Other vital signs
            $table->json('lab_results')->nullable(); // Laboratory test results
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['pregnancy_record_id', 'measurement_date'], 'preg_health_record_date_idx');
            $table->index(['user_id', 'measurement_date'], 'preg_health_user_date_idx');
        });

        // Pregnancy Risk Factors Table
        Schema::create('pregnancy_risk_factors', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('pregnancy_record_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('risk_factor_type'); // age, medical_condition, lifestyle, etc.
            $table->string('risk_factor_name');
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->text('description')->nullable();
            $table->json('risk_data')->nullable(); // Additional risk factor data
            $table->boolean('is_managed')->default(false);
            $table->text('management_plan')->nullable();
            $table->date('identified_date');
            $table->date('resolved_date')->nullable();
            $table->timestamps();
            
            $table->index(['pregnancy_record_id', 'risk_level'], 'preg_risk_record_level_idx');
            $table->index(['user_id', 'risk_level'], 'preg_risk_user_level_idx');
            $table->index('risk_factor_type', 'preg_risk_type_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pregnancy_risk_factors');
        Schema::dropIfExists('pregnancy_health_metrics');
        Schema::dropIfExists('baby_development_milestones');
        Schema::dropIfExists('pregnancy_appointments');
        Schema::dropIfExists('pregnancy_symptoms');
        Schema::dropIfExists('pregnancy_records');
    }
}; 