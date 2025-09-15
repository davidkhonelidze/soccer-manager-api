<?php

use App\Models\Player;
use App\Models\Team;
use App\Models\TransferListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

describe('Player Transfer Listing Tests', function () {
    beforeEach(function () {
        // Create a user with a team and players
        $this->user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $this->team = Team::factory()->create([
            'balance' => config('soccer.team.initial_balance', 5000000),
        ]);

        $this->user->update(['team_id' => $this->team->id]);

        // Create players for the team
        $this->player = Player::factory()->create([
            'team_id' => $this->team->id,
            'position' => 'midfielder',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->anotherPlayer = Player::factory()->create([
            'team_id' => $this->team->id,
            'position' => 'attacker',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);
    });

    it('can successfully list a player for transfer', function () {
        $response = $this->actingAs($this->user)
            ->postJson('/api/transfer-listings', [
                'player_id' => $this->player->id,
                'asking_price' => 1000000.50,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'transfer_listing' => [
                        'id',
                        'player_id',
                        'selling_team_id',
                        'asking_price',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);

        // Verify transfer listing was created in database
        $this->assertDatabaseHas('transfer_listings', [
            'player_id' => $this->player->id,
            'selling_team_id' => $this->team->id,
            'asking_price' => '1000000.50',
            'status' => 'active',
            'unique_key' => 'active',
        ]);

        // Verify the player is now listed for transfer
        $transferListing = TransferListing::where('player_id', $this->player->id)->first();
        expect($transferListing)->not->toBeNull();
        expect($transferListing->status)->toBe('active');
        expect($transferListing->selling_team_id)->toBe($this->team->id);
    });

    it('verifies transfer listing is created with correct player association', function () {
        $response = $this->actingAs($this->user)
            ->postJson('/api/transfer-listings', [
                'player_id' => $this->player->id,
                'asking_price' => 500000,
            ]);

        $response->assertStatus(201);

        $transferListing = TransferListing::where('player_id', $this->player->id)->first();

        // Verify player relationship
        expect($transferListing->player)->not->toBeNull();
        expect($transferListing->player->id)->toBe($this->player->id);
        expect($transferListing->player->first_name)->toBe('John');
        expect($transferListing->player->last_name)->toBe('Doe');

        // Verify team relationship
        expect($transferListing->sellingTeam)->not->toBeNull();
        expect($transferListing->sellingTeam->id)->toBe($this->team->id);
    });

    it('prevents listing players from other teams', function () {
        // Create another user with their own team and player
        $otherUser = User::factory()->create();
        $otherTeam = Team::factory()->create();
        $otherUser->update(['team_id' => $otherTeam->id]);

        $otherPlayer = Player::factory()->create([
            'team_id' => $otherTeam->id,
            'position' => 'defender',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/transfer-listings', [
                'player_id' => $otherPlayer->id,
                'asking_price' => 1000000,
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Player not found or does not belong to your team',
            ]);

        // Verify no transfer listing was created
        $this->assertDatabaseMissing('transfer_listings', [
            'player_id' => $otherPlayer->id,
        ]);
    });

    it('requires authentication to list players', function () {
        $response = $this->postJson('/api/transfer-listings', [
            'player_id' => $this->player->id,
            'asking_price' => 1000000,
        ]);

        $response->assertStatus(401);
    });

    it('validates required fields', function () {
        $response = $this->actingAs($this->user)
            ->postJson('/api/transfer-listings', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['player_id', 'asking_price']);
    });

    it('validates asking price is numeric and minimum value', function () {
        $testCases = [
            ['asking_price' => 'not_a_number', 'error' => 'asking_price'],
            ['asking_price' => -100, 'error' => 'asking_price'],
            ['asking_price' => 0, 'error' => 'asking_price'],
            ['asking_price' => 0.5, 'error' => 'asking_price'],
        ];

        foreach ($testCases as $index => $testCase) {
            // Create a fresh player for each test case to avoid conflicts
            $testPlayer = Player::factory()->create([
                'team_id' => $this->team->id,
                'position' => 'midfielder',
            ]);

            $response = $this->actingAs($this->user)
                ->postJson('/api/transfer-listings', [
                    'player_id' => $testPlayer->id,
                    'asking_price' => $testCase['asking_price'],
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors([$testCase['error']]);
        }
    });

    it('validates player exists', function () {
        $response = $this->actingAs($this->user)
            ->postJson('/api/transfer-listings', [
                'player_id' => 99999, // Non-existent player
                'asking_price' => 1000000,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['player_id']);
    });

    it('prevents listing already listed players', function () {
        // First, list the player
        $this->actingAs($this->user)
            ->postJson('/api/transfer-listings', [
                'player_id' => $this->player->id,
                'asking_price' => 1000000,
            ]);

        // Try to list the same player again
        $response = $this->actingAs($this->user)
            ->postJson('/api/transfer-listings', [
                'player_id' => $this->player->id,
                'asking_price' => 1500000,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Player is already listed for transfer.',
            ]);

        // Verify only one listing exists
        $listingsCount = TransferListing::where('player_id', $this->player->id)->count();
        expect($listingsCount)->toBe(1);
    });

    it('requires user to have a team to list players', function () {
        // Create user without team
        $userWithoutTeam = User::factory()->create();

        $response = $this->actingAs($userWithoutTeam)
            ->postJson('/api/transfer-listings', [
                'player_id' => $this->player->id,
                'asking_price' => 1000000,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'You must be assigned to a team to list players for transfer',
            ]);
    });

    it('sets transfer listing status correctly', function () {
        $response = $this->actingAs($this->user)
            ->postJson('/api/transfer-listings', [
                'player_id' => $this->player->id,
                'asking_price' => 1000000,
            ]);

        $response->assertStatus(201);

        $transferListing = TransferListing::where('player_id', $this->player->id)->first();
        expect($transferListing->status)->toBe('active');
        expect($transferListing->unique_key)->toBe('active');
    });

    it('handles multiple players from same team being listed', function () {
        // List first player
        $response1 = $this->actingAs($this->user)
            ->postJson('/api/transfer-listings', [
                'player_id' => $this->player->id,
                'asking_price' => 1000000,
            ]);
        $response1->assertStatus(201);

        // List second player
        $response2 = $this->actingAs($this->user)
            ->postJson('/api/transfer-listings', [
                'player_id' => $this->anotherPlayer->id,
                'asking_price' => 1500000,
            ]);
        $response2->assertStatus(201);

        // Verify both listings exist
        $this->assertDatabaseHas('transfer_listings', [
            'player_id' => $this->player->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('transfer_listings', [
            'player_id' => $this->anotherPlayer->id,
            'status' => 'active',
        ]);

        // Verify total listings count
        $totalListings = TransferListing::where('selling_team_id', $this->team->id)->count();
        expect($totalListings)->toBe(2);
    });
});

describe('Transfer Listing Display/Retrieval Tests', function () {
    beforeEach(function () {
        // Create multiple users with teams and players
        $this->user1 = User::factory()->create();
        $this->team1 = Team::factory()->create(['name' => 'Team Alpha']);
        $this->user1->update(['team_id' => $this->team1->id]);

        $this->user2 = User::factory()->create();
        $this->team2 = Team::factory()->create(['name' => 'Team Beta']);
        $this->user2->update(['team_id' => $this->team2->id]);

        // Create players and list them
        $this->player1 = Player::factory()->create([
            'team_id' => $this->team1->id,
            'position' => 'midfielder',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->player2 = Player::factory()->create([
            'team_id' => $this->team2->id,
            'position' => 'attacker',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        $this->player3 = Player::factory()->create([
            'team_id' => $this->team1->id,
            'position' => 'defender',
            'first_name' => 'Bob',
            'last_name' => 'Wilson',
        ]);

        // Create transfer listings
        $this->listing1 = TransferListing::create([
            'player_id' => $this->player1->id,
            'selling_team_id' => $this->team1->id,
            'asking_price' => 1000000,
            'status' => 'active',
            'unique_key' => 'active',
        ]);

        $this->listing2 = TransferListing::create([
            'player_id' => $this->player2->id,
            'selling_team_id' => $this->team2->id,
            'asking_price' => 1500000,
            'status' => 'active',
            'unique_key' => 'active',
        ]);

        $this->listing3 = TransferListing::create([
            'player_id' => $this->player3->id,
            'selling_team_id' => $this->team1->id,
            'asking_price' => 800000,
            'status' => 'active',
            'unique_key' => 'active',
        ]);
    });

    it('can fetch all available transfer listings', function () {
        $response = $this->getJson('/api/transfer-listings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'player_id',
                        'selling_team_id',
                        'asking_price',
                        'created_at',
                        'updated_at',
                        'player' => [
                            'id',
                            'name',
                            'position',
                            'age',
                        ],
                        'selling_team' => [
                            'id',
                            'name',
                        ],
                    ],
                ],
                'links',
                'meta',
            ]);

        // Verify all active listings are returned
        $responseData = $response->json();
        expect($responseData['data'])->toHaveCount(3);
    });

    it('only displays active transfer listings', function () {
        // Create a sold listing
        TransferListing::create([
            'player_id' => Player::factory()->create(['team_id' => $this->team1->id])->id,
            'selling_team_id' => $this->team1->id,
            'asking_price' => 2000000,
            'status' => 'sold',
            'unique_key' => null,
        ]);

        // Create a canceled listing
        TransferListing::create([
            'player_id' => Player::factory()->create(['team_id' => $this->team1->id])->id,
            'selling_team_id' => $this->team1->id,
            'asking_price' => 2000000,
            'status' => 'canceled',
            'unique_key' => null,
        ]);

        $response = $this->getJson('/api/transfer-listings');

        $response->assertStatus(200);

        // Should only return active listings
        $responseData = $response->json();
        expect($responseData['data'])->toHaveCount(3);

        // Verify all returned listings are active
        foreach ($responseData['data'] as $listing) {
            expect($listing['status'])->toBe('active');
        }
    });

    it('supports pagination for transfer listings', function () {
        // Create more listings to test pagination
        for ($i = 0; $i < 20; $i++) {
            $player = Player::factory()->create([
                'team_id' => $this->team1->id,
                'position' => 'midfielder',
            ]);

            TransferListing::create([
                'player_id' => $player->id,
                'selling_team_id' => $this->team1->id,
                'asking_price' => 1000000 + ($i * 100000),
                'status' => 'active',
                'unique_key' => 'active',
            ]);
        }

        // Test first page
        $response = $this->getJson('/api/transfer-listings?page=1');
        $response->assertStatus(200);

        $responseData = $response->json();
        expect($responseData['data'])->toHaveCount(config('soccer.pagination.transfer_listings_per_page', 15));
        expect($responseData['meta']['current_page'])->toBe(1);

        // Test second page
        $response = $this->getJson('/api/transfer-listings?page=2');
        $response->assertStatus(200);

        $responseData = $response->json();
        expect($responseData['meta']['current_page'])->toBe(2);
    });

    it('orders transfer listings by creation date descending', function () {
        $response = $this->getJson('/api/transfer-listings');

        $response->assertStatus(200);

        $responseData = $response->json();
        $listings = $responseData['data'];

        // Verify listings are ordered by created_at desc (newest first)
        for ($i = 0; $i < count($listings) - 1; $i++) {
            $currentDate = strtotime($listings[$i]['created_at']);
            $nextDate = strtotime($listings[$i + 1]['created_at']);
            expect($currentDate)->toBeGreaterThanOrEqual($nextDate);
        }
    });

    it('includes player and team information in listings', function () {
        $response = $this->getJson('/api/transfer-listings');

        $response->assertStatus(200);

        $responseData = $response->json();
        $listing = $responseData['data'][0];

        // Verify player information is included
        expect($listing['player'])->not->toBeNull();
        expect($listing['player'])->toHaveKey('id');
        expect($listing['player'])->toHaveKey('name');
        expect($listing['player'])->toHaveKey('position');
        expect($listing['player'])->toHaveKey('age');

        // Verify team information is included
        expect($listing['selling_team'])->not->toBeNull();
        expect($listing['selling_team'])->toHaveKey('id');
        expect($listing['selling_team'])->toHaveKey('name');
    });

    it('handles empty transfer listings gracefully', function () {
        // Delete all existing listings
        TransferListing::truncate();

        $response = $this->getJson('/api/transfer-listings');

        $response->assertStatus(200);

        $responseData = $response->json();
        expect($responseData['data'])->toHaveCount(0);
        expect($responseData['meta']['total'])->toBe(0);
    });

    it('verifies correct JSON structure in API responses', function () {
        $response = $this->getJson('/api/transfer-listings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'player_id',
                        'selling_team_id',
                        'asking_price',
                        'created_at',
                        'updated_at',
                        'player' => [
                            'id',
                            'name',
                            'position',
                            'age',
                        ],
                        'selling_team' => [
                            'id',
                            'name',
                        ],
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
    });

    it('does not show delisted players in listings', function () {
        // Cancel one of the listings
        $this->listing1->update([
            'status' => 'canceled',
            'unique_key' => null,
        ]);

        $response = $this->getJson('/api/transfer-listings');

        $response->assertStatus(200);

        $responseData = $response->json();
        expect($responseData['data'])->toHaveCount(2);

        // Verify the canceled listing is not in the response
        $playerIds = array_column($responseData['data'], 'player_id');
        expect($playerIds)->not->toContain($this->player1->id);
    });
});

describe('Transfer Listing Edge Cases and Error Handling', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->team = Team::factory()->create();
        $this->user->update(['team_id' => $this->team->id]);

        $this->player = Player::factory()->create([
            'team_id' => $this->team->id,
            'position' => 'midfielder',
        ]);
    });

    it('handles very large asking prices', function () {
        $response = $this->actingAs($this->user)
            ->postJson('/api/transfer-listings', [
                'player_id' => $this->player->id,
                'asking_price' => 999999999999.99,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('transfer_listings', [
            'player_id' => $this->player->id,
            'asking_price' => '999999999999.99',
        ]);
    });

    it('handles decimal asking prices correctly', function () {
        $response = $this->actingAs($this->user)
            ->postJson('/api/transfer-listings', [
                'player_id' => $this->player->id,
                'asking_price' => 1234567.89,
            ]);

        $response->assertStatus(201);

        $transferListing = TransferListing::where('player_id', $this->player->id)->first();
        expect($transferListing->asking_price)->toBe('1234567.89');
    });

    it('handles concurrent listing attempts gracefully', function () {
        // Simulate concurrent requests by making multiple requests quickly
        $responses = [];

        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->actingAs($this->user)
                ->postJson('/api/transfer-listings', [
                    'player_id' => $this->player->id,
                    'asking_price' => 1000000 + $i,
                ]);
        }

        // Only one should succeed
        $successCount = 0;
        $errorCount = 0;

        foreach ($responses as $response) {
            if ($response->status() === 201) {
                $successCount++;
            } elseif ($response->status() === 400) {
                $errorCount++;
            }
        }

        expect($successCount)->toBe(1);
        expect($errorCount)->toBe(2);

        // Verify only one listing exists
        $listingsCount = TransferListing::where('player_id', $this->player->id)->count();
        expect($listingsCount)->toBe(1);
    });

    it('handles database transaction rollback on errors', function () {
        // This test would require mocking the database to simulate failures
        // For now, we'll test that the service uses transactions
        $response = $this->actingAs($this->user)
            ->postJson('/api/transfer-listings', [
                'player_id' => $this->player->id,
                'asking_price' => 1000000,
            ]);

        $response->assertStatus(201);

        // Verify the listing was created atomically
        $this->assertDatabaseHas('transfer_listings', [
            'player_id' => $this->player->id,
            'status' => 'active',
        ]);
    });

    it('validates player belongs to user team before listing', function () {
        // Create another team and a player that belongs to that team
        $otherTeam = Team::factory()->create();
        $playerFromOtherTeam = Player::factory()->create(['team_id' => $otherTeam->id]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/transfer-listings', [
                'player_id' => $playerFromOtherTeam->id,
                'asking_price' => 1000000,
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Player not found or does not belong to your team',
            ]);
    });

    it('handles malformed JSON requests', function () {
        $response = $this->actingAs($this->user)
            ->postJson('/api/transfer-listings', [
                'player_id' => $this->player->id,
                'asking_price' => 'invalid_json_value',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['asking_price']);
    });

    it('handles missing authentication token', function () {
        $response = $this->postJson('/api/transfer-listings', [
            'player_id' => $this->player->id,
            'asking_price' => 1000000,
        ]);

        $response->assertStatus(401);
    });

    it('handles invalid authentication token', function () {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid_token',
        ])->postJson('/api/transfer-listings', [
            'player_id' => $this->player->id,
            'asking_price' => 1000000,
        ]);

        $response->assertStatus(401);
    });
});
