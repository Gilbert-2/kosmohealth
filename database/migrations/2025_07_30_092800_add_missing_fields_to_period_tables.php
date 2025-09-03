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
        // Add missing columns to period_cycles table
        Schema::table('period_cycles', function (Blueprint $table) {
            if (!Schema::hasColumn('period_cycles', 'cycle_length')) {
                $table->integer('cycle_length')->nullable()->after('end_date');
            }
            if (!Schema::hasColumn('period_cycles', 'mood')) {
                $table->string('mood')->nullable()->after('flow_intensity');
            }
        });

        // Add missing columns to period_symptoms table
        Schema::table('period_symptoms', function (Blueprint $table) {
            if (!Schema::hasColumn('period_symptoms', 'cycle_id')) {
                $table->unsignedBigInteger('cycle_id')->nullable()->after('user_id');
                $table->foreign('cycle_id')->references('id')->on('period_cycles')->onDelete('cascade');
            }
            if (!Schema::hasColumn('period_symptoms', 'flow_intensity')) {
                $table->enum('flow_intensity', ['Light', 'Medium', 'Heavy'])->nullable()->after('symptom');
            }
            if (!Schema::hasColumn('period_symptoms', 'mood')) {
                $table->string('mood')->nullable()->after('flow_intensity');
            }
            if (!Schema::hasColumn('period_symptoms', 'notes')) {
                $table->text('notes')->nullable()->after('mood');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove columns from period_symptoms table
        Schema::table('period_symptoms', function (Blueprint $table) {
            $table->dropForeign(['cycle_id']);
            $table->dropColumn(['cycle_id', 'flow_intensity', 'mood', 'notes']);
        });

        // Remove columns from period_cycles table
        Schema::table('period_cycles', function (Blueprint $table) {
            $table->dropColumn(['cycle_length', 'mood']);
        });
    }
};
