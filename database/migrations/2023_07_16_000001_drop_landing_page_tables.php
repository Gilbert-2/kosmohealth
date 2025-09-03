<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropLandingPageTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Drop the landing_page_items table first because it has a foreign key to landing_page_sections
        Schema::dropIfExists('landing_page_items');
        Schema::dropIfExists('landing_page_sections');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // We're not recreating the tables in the down method
        // If needed, the original migrations can be run again
    }
}
