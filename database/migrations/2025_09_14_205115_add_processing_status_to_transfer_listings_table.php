<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'processing' status to the enum
        DB::statement("ALTER TABLE transfer_listings MODIFY COLUMN status ENUM('active', 'processing', 'sold', 'canceled') DEFAULT 'active'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'processing' status from the enum
        DB::statement("ALTER TABLE transfer_listings MODIFY COLUMN status ENUM('active', 'sold', 'canceled') DEFAULT 'active'");
    }
};