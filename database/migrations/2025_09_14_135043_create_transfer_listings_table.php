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
        Schema::create('transfer_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->foreignId('selling_team_id')->constrained('teams');
            $table->decimal('asking_price', 15, 2);
            $table->enum('status', ['active', 'sold', 'canceled'])->default('active');
            $table->string('unique_key', 10)->nullable()->default('active')->comment('To avoid duplicate active transfers');
            $table->timestamps();

            $table->index(['player_id', 'status']);
            $table->index('selling_team_id');
            $table->index('asking_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_listings');
    }
};
