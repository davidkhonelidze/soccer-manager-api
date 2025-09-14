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
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 30);
            $table->string('last_name', 30);
            $table->date('date_of_birth');
            $table->enum('position', array_keys(config('soccer.team.positions')));
            $table->foreignId('country_id')->constrained('countries');
            $table->foreignId('team_id')->constrained('teams');
            $table->decimal('value', 15, 2)
                ->default(config('soccer.player.initial_value'));
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
