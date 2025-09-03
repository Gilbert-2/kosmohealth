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
        Schema::table('period_cycles', function (Blueprint $table) {
            $table->string('flow_intensity')->nullable()->after('end_date');
            $table->text('notes')->nullable()->after('flow_intensity');
            $table->boolean('is_predicted')->default(false)->after('notes');
            $table->json('symptoms')->nullable()->after('is_predicted');
            $table->decimal('cycle_length', 5, 2)->nullable()->after('symptoms');
            $table->integer('confidence_score')->nullable()->after('cycle_length');
            
            // Add indexes for better performance
            $table->index(['user_id', 'start_date']);
            $table->index(['user_id', 'is_predicted']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('period_cycles', function (Blueprint $table) {
            $table->dropColumn([
                'flow_intensity',
                'notes',
                'is_predicted',
                'symptoms',
                'cycle_length',
                'confidence_score'
            ]);
            
            $table->dropIndex(['user_id', 'start_date']);
            $table->dropIndex(['user_id', 'is_predicted']);
        });
    }
};