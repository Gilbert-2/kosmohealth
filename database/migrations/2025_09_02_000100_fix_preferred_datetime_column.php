<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Convert preferred_datetime to DATETIME to avoid implicit ON UPDATE CURRENT_TIMESTAMP
        // which can cause the value to change on row updates (e.g., when assigning host)
        DB::statement('ALTER TABLE consultation_bookings MODIFY preferred_datetime DATETIME NOT NULL');
    }

    public function down(): void
    {
        // Revert to TIMESTAMP if needed (may reintroduce ON UPDATE behavior depending on SQL mode)
        DB::statement('ALTER TABLE consultation_bookings MODIFY preferred_datetime TIMESTAMP NOT NULL');
    }
};


