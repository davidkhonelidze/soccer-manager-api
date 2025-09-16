<?php

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

    $this->country1 = \App\Models\Country::first();
    $this->country2 = \App\Models\Country::skip(1)->first();
});

describe('Team Update Functionality', function () {
    describe('Successful Team Updates', function () {
        it('successfully updates team name and country', function () {
            $updateData = [
                'name' => 'Updated Team Name',
                'country_id' => $this->country2->id,
            ];

            $response = $this->actingAs($this->user1)
                ->putJson("/api/teams/{$this->team1->id}", $updateData);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Team updated successfully',
                ])
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'uuid',
                        'name',
                        'balance',
                        'created_at',
                        'updated_at',
                    ],
                ]);

            // Verify the team was updated in the database
            $this->assertDatabaseHas('teams', [
                'id' => $this->team1->id,
                'name' => 'Updated Team Name',
                'country_id' => $this->country2->id,
            ]);

            // Verify the response contains updated data
            $responseData = $response->json('data');
            expect($responseData['name'])->toBe('Updated Team Name');
            expect($responseData['id'])->toBe($this->team1->id);
        });

        it('updates only the allowed fields', function () {
            // Get original values before update
            $originalBalance = $this->team1->balance;
            $originalUuid = (string) $this->team1->uuid;

            $updateData = [
                'name' => 'New Team Name',
                'country_id' => $this->country2->id,
            ];

            $response = $this->actingAs($this->user1)
                ->putJson("/api/teams/{$this->team1->id}", $updateData);

            $response->assertStatus(200);

            // Verify only name and country_id were updated
            $updatedTeam = Team::find($this->team1->id);
            expect($updatedTeam->name)->toBe('New Team Name');
            expect($updatedTeam->country_id)->toBe($this->country2->id);

            // Balance should remain unchanged
            expect($updatedTeam->balance)->toEqual($originalBalance);

            // UUID should remain unchanged (convert both to strings for comparison)
            expect((string) $updatedTeam->uuid)->toBe($originalUuid);
        });

        it('includes updated team information in response', function () {
            $updateData = [
                'name' => 'Response Test Team',
                'country_id' => $this->country1->id,
            ];

            $response = $this->actingAs($this->user1)
                ->putJson("/api/teams/{$this->team1->id}", $updateData);

            $response->assertStatus(200);

            $responseData = $response->json('data');
            expect($responseData)->toHaveKeys(['id', 'uuid', 'name', 'balance', 'created_at', 'updated_at']);
            expect($responseData['name'])->toBe('Response Test Team');
            expect($responseData['id'])->toBe($this->team1->id);
        });
    });

    describe('Authorization Tests', function () {
        it('prevents users from updating other teams', function () {
            $updateData = [
                'name' => 'Hacker Attempt',
                'country_id' => $this->country1->id,
            ];

            $response = $this->actingAs($this->user1)
                ->putJson("/api/teams/{$this->team2->id}", $updateData);

            $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'message' => 'You can only update your own team',
                ]);

            // Verify the team was not updated
            $this->assertDatabaseHas('teams', [
                'id' => $this->team2->id,
                'name' => $this->team2->name, // Original name should remain
            ]);
        });

        it('prevents users without teams from updating teams', function () {
            $userWithoutTeam = User::factory()->create(['team_id' => null]);

            $updateData = [
                'name' => 'Unauthorized Update',
                'country_id' => $this->country1->id,
            ];

            $response = $this->actingAs($userWithoutTeam)
                ->putJson("/api/teams/{$this->team1->id}", $updateData);

            $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'message' => 'You must be assigned to a team to update team information',
                ]);
        });

        it('requires authentication', function () {
            $updateData = [
                'name' => 'Unauthorized Update',
                'country_id' => $this->country1->id,
            ];

            $response = $this->putJson("/api/teams/{$this->team1->id}", $updateData);

            $response->assertStatus(401);
        });

        it('handles non-existent team', function () {
            $updateData = [
                'name' => 'Non-existent Team',
                'country_id' => $this->country1->id,
            ];

            $response = $this->actingAs($this->user1)
                ->putJson('/api/teams/99999', $updateData);

            $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Team not found',
                ]);
        });

        it('handles invalid team ID format', function () {
            $updateData = [
                'name' => 'Invalid ID Test',
                'country_id' => $this->country1->id,
            ];

            $response = $this->actingAs($this->user1)
                ->putJson('/api/teams/invalid', $updateData);

            $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Team not found',
                ]);
        });
    });

    describe('Validation Tests', function () {
        it('validates required fields', function () {
            $response = $this->actingAs($this->user1)
                ->putJson("/api/teams/{$this->team1->id}", []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'country_id']);
        });

        it('validates team name minimum length', function () {
            $updateData = [
                'name' => 'A', // Too short
                'country_id' => $this->country1->id,
            ];

            $response = $this->actingAs($this->user1)
                ->putJson("/api/teams/{$this->team1->id}", $updateData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
        });

        it('validates team name maximum length', function () {
            $updateData = [
                'name' => str_repeat('A', 101), // Too long (max 100)
                'country_id' => $this->country1->id,
            ];

            $response = $this->actingAs($this->user1)
                ->putJson("/api/teams/{$this->team1->id}", $updateData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
        });

        it('validates team name is string', function () {
            $updateData = [
                'name' => 123, // Not a string
                'country_id' => $this->country1->id,
            ];

            $response = $this->actingAs($this->user1)
                ->putJson("/api/teams/{$this->team1->id}", $updateData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
        });

        it('validates country_id is integer', function () {
            $updateData = [
                'name' => 'Valid Team Name',
                'country_id' => 'not_an_integer',
            ];

            $response = $this->actingAs($this->user1)
                ->putJson("/api/teams/{$this->team1->id}", $updateData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['country_id']);
        });

        it('validates country exists', function () {
            $updateData = [
                'name' => 'Valid Team Name',
                'country_id' => 99999, // Non-existent country
            ];

            $response = $this->actingAs($this->user1)
                ->putJson("/api/teams/{$this->team1->id}", $updateData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['country_id']);
        });

        it('validates country_id is positive', function () {
            $updateData = [
                'name' => 'Valid Team Name',
                'country_id' => -1, // Negative ID
            ];

            $response = $this->actingAs($this->user1)
                ->putJson("/api/teams/{$this->team1->id}", $updateData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['country_id']);
        });

        it('validates team name uniqueness', function () {
            $updateData = [
                'name' => $this->team2->name, // Use name from another team
                'country_id' => $this->country1->id,
            ];

            $response = $this->actingAs($this->user1)
                ->putJson("/api/teams/{$this->team1->id}", $updateData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
        });

        it('allows team to keep its current name', function () {
            $updateData = [
                'name' => $this->team1->name, // Same name as current team
                'country_id' => $this->country2->id,
            ];

            $response = $this->actingAs($this->user1)
                ->putJson("/api/teams/{$this->team1->id}", $updateData);

            $response->assertStatus(200);
        });
    });

    describe('Error Handling Tests', function () {
        it('handles malformed JSON requests', function () {
            $response = $this->actingAs($this->user1)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->call('PUT', "/api/teams/{$this->team1->id}", [], [], [], [],
                    '{"name": "Test Team", "country_id": }' // Malformed JSON
                );

            // Malformed JSON should not succeed (should not be 200)
            expect($response->status())->not->toBe(200);

            // Should be some kind of error response
            expect($response->status())->toBeIn([400, 422, 302]);
        });

        it('handles valid JSON with invalid data', function () {
            $response = $this->actingAs($this->user1)
                ->putJson("/api/teams/{$this->team1->id}", [
                    'name' => '', // Invalid: empty name
                    'country_id' => 'invalid', // Invalid: not an integer
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'country_id']);
        });

        it('handles missing authentication token', function () {
            $updateData = [
                'name' => 'Test Team',
                'country_id' => $this->country1->id,
            ];

            $response = $this->putJson("/api/teams/{$this->team1->id}", $updateData);

            $response->assertStatus(401);
        });

        it('handles invalid authentication token', function () {
            $updateData = [
                'name' => 'Test Team',
                'country_id' => $this->country1->id,
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer invalid_token',
            ])->putJson("/api/teams/{$this->team1->id}", $updateData);

            $response->assertStatus(401);
        });
    });

    describe('Internationalization Tests', function () {
        it('returns English messages by default', function () {
            $updateData = [
                'name' => 'Test Team',
                'country_id' => $this->country1->id,
            ];

            $response = $this->actingAs($this->user1)
                ->putJson("/api/teams/{$this->team1->id}", $updateData);

            $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Team updated successfully',
                ]);
        });

        it('returns Georgian messages when Accept-Language is ka', function () {
            $updateData = [
                'name' => 'Test Team',
                'country_id' => $this->country1->id,
            ];

            $response = $this->actingAs($this->user1)
                ->withHeaders(['Accept-Language' => 'ka'])
                ->putJson("/api/teams/{$this->team1->id}", $updateData);

            $response->assertStatus(200)
                ->assertJson([
                    'message' => 'გუნდი წარმატებით განახლდა',
                ]);
        });

        it('returns Georgian error messages when Accept-Language is ka', function () {
            $response = $this->actingAs($this->user1)
                ->withHeaders(['Accept-Language' => 'ka'])
                ->putJson("/api/teams/{$this->team2->id}", [
                    'name' => 'Test Team',
                    'country_id' => $this->country1->id,
                ]);

            $response->assertStatus(403)
                ->assertJson([
                    'message' => 'შეგიძლიათ მხოლოდ თქვენი გუნდის განახლება',
                ]);
        });
    });

    describe('Edge Cases', function () {
        it('handles team name with exactly 100 characters', function () {
            $longName = str_repeat('A', 100); // Exactly 100 characters

            $updateData = [
                'name' => $longName,
                'country_id' => $this->country1->id,
            ];

            $response = $this->actingAs($this->user1)
                ->putJson("/api/teams/{$this->team1->id}", $updateData);

            $response->assertStatus(200);

            $this->assertDatabaseHas('teams', [
                'id' => $this->team1->id,
                'name' => $longName,
            ]);
        });

        it('handles team name with exactly 2 characters', function () {
            $shortName = 'AB'; // Exactly 2 characters

            $updateData = [
                'name' => $shortName,
                'country_id' => $this->country1->id,
            ];

            $response = $this->actingAs($this->user1)
                ->putJson("/api/teams/{$this->team1->id}", $updateData);

            $response->assertStatus(200);

            $this->assertDatabaseHas('teams', [
                'id' => $this->team1->id,
                'name' => $shortName,
            ]);
        });

        it('handles updating to the same values', function () {
            $updateData = [
                'name' => $this->team1->name, // Same name
                'country_id' => $this->team1->country_id, // Same country
            ];

            $response = $this->actingAs($this->user1)
                ->putJson("/api/teams/{$this->team1->id}", $updateData);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Team updated successfully',
                ]);
        });
    });
});
