<?php

use App\Http\Resources\UserResource;
use App\Models\Country;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();

    // Create test data
    $this->country = Country::first();
    $this->team = Team::factory()->create(['country_id' => $this->country->id]);
    $this->user = User::factory()->create(['team_id' => $this->team->id]);
});

describe('Team Value Calculation', function () {
    it('calculates team value as sum of all players values', function () {
        // Create players with different values
        $player1 = Player::factory()->create([
            'team_id' => $this->team->id,
            'value' => 1000000.00,
        ]);

        $player2 = Player::factory()->create([
            'team_id' => $this->team->id,
            'value' => 2000000.00,
        ]);

        $player3 = Player::factory()->create([
            'team_id' => $this->team->id,
            'value' => 1500000.00,
        ]);

        // Load user with team relationship and calculate players value
        $this->user->load([
            'team' => function ($query) {
                $query->with('country')
                    ->withSum('players', 'value');
            },
        ]);

        // Create UserResource and get the array
        $resource = new UserResource($this->user);
        $data = $resource->toArray(request());

        // Verify team value is calculated correctly
        expect($data['team']['value'])->toEqual(4500000.00);
    });

    it('returns zero value for team with no players', function () {
        // Load user with team relationship (no players created)
        $this->user->load('team');

        // Create UserResource and get the array
        $resource = new UserResource($this->user);
        $data = $resource->toArray(request());

        // Verify team value is zero (withSum returns null, but UserResource converts to 0.0)
        expect($data['team']['value'])->toEqual(0.0);
    });

    it('handles decimal values correctly', function () {
        // Create players with decimal values
        $player1 = Player::factory()->create([
            'team_id' => $this->team->id,
            'value' => 1234567.89,
        ]);

        $player2 = Player::factory()->create([
            'team_id' => $this->team->id,
            'value' => 987654.32,
        ]);

        // Load user with team relationship and calculate players value
        $this->user->load([
            'team' => function ($query) {
                $query->with('country')
                    ->withSum('players', 'value');
            },
        ]);

        // Create UserResource and get the array
        $resource = new UserResource($this->user);
        $data = $resource->toArray(request());

        // Verify team value handles decimals correctly
        expect($data['team']['value'])->toEqual(2222222.21);
    });

    it('includes team value in response structure', function () {
        // Create a player
        Player::factory()->create([
            'team_id' => $this->team->id,
            'value' => 1000000.00,
        ]);

        // Load user with team relationship and calculate players value
        $this->user->load([
            'team' => function ($query) {
                $query->with('country')
                    ->withSum('players', 'value');
            },
        ]);

        // Create UserResource and get the array
        $resource = new UserResource($this->user);
        $data = $resource->toArray(request());

        // Verify team value field exists in response
        expect($data['team'])->toHaveKey('value');
        expect($data['team']['value'])->toBeNumeric();
    });

    it('calculates value for team with many players', function () {
        // Create 20 players with random values
        $totalExpectedValue = 0;
        for ($i = 0; $i < 20; $i++) {
            $value = rand(500000, 5000000);
            Player::factory()->create([
                'team_id' => $this->team->id,
                'value' => $value,
            ]);
            $totalExpectedValue += $value;
        }

        // Load user with team relationship and calculate players value
        $this->user->load([
            'team' => function ($query) {
                $query->with('country')
                    ->withSum('players', 'value');
            },
        ]);

        // Create UserResource and get the array
        $resource = new UserResource($this->user);
        $data = $resource->toArray(request());

        // Verify team value is calculated correctly for many players
        expect($data['team']['value'])->toEqual($totalExpectedValue);
    });
});
