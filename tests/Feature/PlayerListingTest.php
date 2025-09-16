<?php

use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();

    // Create test users and teams
    $this->user1 = User::factory()->create();
    $this->user2 = User::factory()->create();

    $this->team1 = Team::factory()->create();
    $this->team2 = Team::factory()->create();

    $this->user1->update(['team_id' => $this->team1->id]);
    $this->user2->update(['team_id' => $this->team2->id]);

    // Create players for both teams
    $this->team1Players = Player::factory()->count(5)->create([
        'team_id' => $this->team1->id,
    ]);

    $this->team2Players = Player::factory()->count(3)->create([
        'team_id' => $this->team2->id,
    ]);
});

describe('Player Listing Endpoint', function () {
    it('requires authentication', function () {
        $response = $this->getJson('/api/players');

        $response->assertStatus(401);
    });

    it('returns paginated list of all players when authenticated', function () {
        $response = $this->actingAs($this->user1)
            ->getJson('/api/players');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'first_name',
                    'last_name',
                    'date_of_birth',
                    'position',
                    'age',
                    'value',
                    'team_id',
                    'country_id',
                    'country' => [
                        'id',
                        'name',
                        'code',
                    ],
                    'team' => [
                        'id',
                        'name',
                        'uuid',
                    ],
                    'created_at',
                    'updated_at',
                ],
            ],
            'links' => [
                'first',
                'last',
                'prev',
                'next',
            ],
            'meta' => [
                'current_page',
                'from',
                'last_page',
                'path',
                'per_page',
                'to',
                'total',
            ],
        ]);

        // Should return all players (5 + 3 = 8 total)
        $responseData = $response->json();
        expect($responseData['meta']['total'])->toBe(8);
        expect($responseData['meta']['per_page'])->toBe(20); // Default from config
    });

    it('filters players by team_id when provided', function () {
        $response = $this->actingAs($this->user1)
            ->getJson("/api/players?team_id={$this->team1->id}");

        $response->assertStatus(200);

        $responseData = $response->json();
        expect($responseData['meta']['total'])->toBe(5); // Only team1 players

        // Verify all returned players belong to team1
        foreach ($responseData['data'] as $player) {
            expect($player['team_id'])->toBe($this->team1->id);
        }
    });

    it('returns empty result when filtering by non-existent team', function () {
        $response = $this->actingAs($this->user1)
            ->getJson('/api/players?team_id=99999');

        $response->assertStatus(200);

        $responseData = $response->json();
        expect($responseData['meta']['total'])->toBe(0);
        expect($responseData['data'])->toBeEmpty();
    });

    it('validates team_id parameter format', function () {
        $response = $this->actingAs($this->user1)
            ->getJson('/api/players?team_id=invalid');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['team_id']);
    });

    it('validates team_id parameter is positive', function () {
        $response = $this->actingAs($this->user1)
            ->getJson('/api/players?team_id=0');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['team_id']);
    });

    it('validates team_id parameter is not negative', function () {
        $response = $this->actingAs($this->user1)
            ->getJson('/api/players?team_id=-1');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['team_id']);
    });

    it('includes country information in player data', function () {
        $response = $this->actingAs($this->user1)
            ->getJson('/api/players');

        $response->assertStatus(200);

        $players = $response->json('data');
        expect($players)->not->toBeEmpty();

        $firstPlayer = $players[0];
        expect($firstPlayer)->toHaveKey('country');
        expect($firstPlayer['country'])->toHaveKeys(['id', 'name', 'code']);
    });

    it('includes team information in player data', function () {
        $response = $this->actingAs($this->user1)
            ->getJson('/api/players');

        $response->assertStatus(200);

        $players = $response->json('data');
        expect($players)->not->toBeEmpty();

        $firstPlayer = $players[0];
        expect($firstPlayer)->toHaveKey('team');
        expect($firstPlayer['team'])->toHaveKeys(['id', 'name', 'uuid']);
    });

    it('respects pagination configuration', function () {
        // Create more players to test pagination
        Player::factory()->count(25)->create([
            'team_id' => $this->team1->id,
        ]);

        $response = $this->actingAs($this->user1)
            ->getJson('/api/players');

        $response->assertStatus(200);

        $responseData = $response->json();
        expect($responseData['meta']['per_page'])->toBe(20); // From config
        expect($responseData['meta']['total'])->toBe(33); // 5 + 3 + 25 = 33
        expect($responseData['meta']['last_page'])->toBe(2); // 33 / 20 = 1.65, rounded up = 2
    });

    it('handles pagination correctly', function () {
        // Create more players to test pagination
        Player::factory()->count(25)->create([
            'team_id' => $this->team1->id,
        ]);

        // Test first page
        $response = $this->actingAs($this->user1)
            ->getJson('/api/players?page=1');

        $response->assertStatus(200);
        $responseData = $response->json();
        expect($responseData['meta']['current_page'])->toBe(1);
        expect($responseData['meta']['from'])->toBe(1);
        expect($responseData['meta']['to'])->toBe(20);

        // Test second page
        $response = $this->actingAs($this->user1)
            ->getJson('/api/players?page=2');

        $response->assertStatus(200);
        $responseData = $response->json();
        expect($responseData['meta']['current_page'])->toBe(2);
        expect($responseData['meta']['from'])->toBe(21);
        expect($responseData['meta']['to'])->toBe(33);
    });

    it('works with different authenticated users', function () {
        $response1 = $this->actingAs($this->user1)
            ->getJson('/api/players');

        $response2 = $this->actingAs($this->user2)
            ->getJson('/api/players');

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        // Both should return the same data (all players)
        $data1 = $response1->json();
        $data2 = $response2->json();

        expect($data1['meta']['total'])->toBe($data2['meta']['total']);
        expect($data1['meta']['total'])->toBe(8); // 5 + 3 = 8
    });

    it('handles empty player list gracefully', function () {
        // Delete all players
        Player::query()->delete();

        $response = $this->actingAs($this->user1)
            ->getJson('/api/players');

        $response->assertStatus(200);

        $responseData = $response->json();
        expect($responseData['meta']['total'])->toBe(0);
        expect($responseData['data'])->toBeEmpty();
        expect($responseData['meta']['current_page'])->toBe(1);
        expect($responseData['meta']['last_page'])->toBe(1);
    });

    it('filters correctly with multiple teams', function () {
        // Create a third team with players
        $team3 = Team::factory()->create();
        Player::factory()->count(4)->create([
            'team_id' => $team3->id,
        ]);

        // Test filtering by team1
        $response = $this->actingAs($this->user1)
            ->getJson("/api/players?team_id={$this->team1->id}");

        $response->assertStatus(200);
        $responseData = $response->json();
        expect($responseData['meta']['total'])->toBe(5);

        // Test filtering by team2
        $response = $this->actingAs($this->user1)
            ->getJson("/api/players?team_id={$this->team2->id}");

        $response->assertStatus(200);
        $responseData = $response->json();
        expect($responseData['meta']['total'])->toBe(3);

        // Test filtering by team3
        $response = $this->actingAs($this->user1)
            ->getJson("/api/players?team_id={$team3->id}");

        $response->assertStatus(200);
        $responseData = $response->json();
        expect($responseData['meta']['total'])->toBe(4);

        // Test without filter (should return all)
        $response = $this->actingAs($this->user1)
            ->getJson('/api/players');

        $response->assertStatus(200);
        $responseData = $response->json();
        expect($responseData['meta']['total'])->toBe(12); // 5 + 3 + 4 = 12
    });
});
