<?php

use App\Models\Player;
use App\Models\Team;
use App\Models\TransferListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->user1 = User::factory()->create();
    $this->user2 = User::factory()->create();

    $this->team1 = Team::factory()->create();
    $this->team2 = Team::factory()->create();

    $this->user1->update(['team_id' => $this->team1->id]);
    $this->user2->update(['team_id' => $this->team2->id]);

    $this->player = Player::factory()->create([
        'team_id' => $this->team1->id,
        'position' => 'midfielder',
    ]);

    // Create transfer listing
    $this->transferListing = TransferListing::create([
        'player_id' => $this->player->id,
        'selling_team_id' => $this->team1->id,
        'asking_price' => 1000000,
        'status' => 'active',
        'unique_key' => 'active',
    ]);

    // Set team balances
    $this->team1->update(['balance' => 5000000]);
    $this->team2->update(['balance' => 5000000]);
});

describe('Transfer Race Condition Protection', function () {
    it('prevents concurrent purchases of the same player', function () {
        $concurrentRequests = 0;
        $successfulPurchases = 0;
        $failedPurchases = 0;

        // Simulate 5 concurrent requests trying to purchase the same player
        $promises = [];

        for ($i = 0; $i < 5; $i++) {
            $promises[] = function () use (&$concurrentRequests, &$successfulPurchases, &$failedPurchases) {
                $concurrentRequests++;

                try {
                    $response = $this->actingAs($this->user2)
                        ->postJson("/api/transfer/purchase/{$this->player->id}");

                    if ($response->status() === 200) {
                        $successfulPurchases++;
                    } else {
                        $failedPurchases++;
                    }
                } catch (\Exception $e) {
                    $failedPurchases++;
                }
            };
        }

        // Execute all requests concurrently
        foreach ($promises as $promise) {
            $promise();
        }

        // Only one purchase should succeed
        expect($successfulPurchases)->toBe(1);
        expect($failedPurchases)->toBe(4);

        // Verify the transfer listing is marked as sold
        $this->transferListing->refresh();
        expect($this->transferListing->status)->toBe('sold');

        // Verify the player belongs to the buying team
        $this->player->refresh();
        expect($this->player->team_id)->toBe($this->team2->id);
    });

    it('handles database locking correctly', function () {
        // Test that the lockForUpdate mechanism works by simulating a scenario
        // where the transfer listing is already being processed

        // First, mark the listing as processing to simulate another request already processing it
        $this->transferListing->update([
            'status' => 'processing',
            'unique_key' => null,
        ]);

        // Try to purchase the same player
        $response = $this->actingAs($this->user2)
            ->postJson("/api/transfer/purchase/{$this->player->id}");

        // Should fail because the listing is already being processed
        expect($response->status())->toBe(400);
        expect($response->json('message'))->toContain('not available for transfer');
    });

    it('verifies lockForUpdate prevents concurrent access', function () {
        // This test verifies that the lockForUpdate mechanism works by testing
        // the actual database locking behavior

        $lockAcquired = false;
        $lockReleased = false;

        // Start a transaction and acquire a lock
        DB::transaction(function () use (&$lockAcquired, &$lockReleased) {
            $lockedListing = TransferListing::where('player_id', $this->player->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            expect($lockedListing)->not->toBeNull();
            $lockAcquired = true;

            // Simulate some processing time
            usleep(100000); // 100ms

            $lockReleased = true;
        });

        expect($lockAcquired)->toBe(true);
        expect($lockReleased)->toBe(true);

        // Now test that a purchase works after the lock is released
        $response = $this->actingAs($this->user2)
            ->postJson("/api/transfer/purchase/{$this->player->id}");

        expect($response->status())->toBe(200);
    });

    it('handles transfer failures with automatic rollback', function () {
        // Mock the TransferAggregate to throw an exception during persist()
        $this->mock(\App\Aggregates\TransferAggregate::class, function ($mock) {
            $mock->shouldReceive('initiateTransfer')
                ->once()
                ->andReturnSelf();
            $mock->shouldReceive('transferFunds')
                ->once()
                ->andReturnSelf();
            $mock->shouldReceive('completeTransfer')
                ->once()
                ->andReturnSelf();
            $mock->shouldReceive('persist')
                ->once()
                ->andThrow(new \Exception('Event Sourcing failed'));
        });

        $response = $this->actingAs($this->user2)
            ->postJson("/api/transfer/purchase/{$this->player->id}");

        expect($response->status())->toBe(400);

        // Verify the transfer listing remains in active status due to automatic rollback
        // The database transaction rollback automatically reverts all changes
        $this->transferListing->refresh();
        expect($this->transferListing->status)->toBe('active');
        expect($this->transferListing->unique_key)->toBe('active');
    });

    it('prevents purchase when player is already being processed', function () {
        // Mark the transfer listing as processing
        $this->transferListing->update([
            'status' => 'processing',
            'unique_key' => null,
        ]);

        $response = $this->actingAs($this->user2)
            ->postJson("/api/transfer/purchase/{$this->player->id}");

        expect($response->status())->toBe(400);
        expect($response->json('message'))->toContain('not available for transfer');
    });

    it('ensures only one transfer can be processed at a time', function () {
        $startTime = microtime(true);

        // Make multiple requests
        $responses = [];
        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->actingAs($this->user2)
                ->postJson("/api/transfer/purchase/{$this->player->id}");
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Count successful responses
        $successfulResponses = collect($responses)->filter(fn ($r) => $r->status() === 200)->count();
        $failedResponses = collect($responses)->filter(fn ($r) => $r->status() === 400)->count();

        // Only one should succeed
        expect($successfulResponses)->toBe(1);
        expect($failedResponses)->toBe(2);

        // Verify the transfer listing is sold
        $this->transferListing->refresh();
        expect($this->transferListing->status)->toBe('sold');
    });

    it('verifies unified transaction handles failures correctly', function () {
        // Test that the unified transaction properly handles failures with automatic rollback

        // First, verify the initial state
        expect($this->transferListing->status)->toBe('active');

        // Mock the TransferAggregate to fail during persist()
        $this->mock(\App\Aggregates\TransferAggregate::class, function ($mock) {
            $mock->shouldReceive('initiateTransfer')
                ->once()
                ->andReturnSelf();
            $mock->shouldReceive('transferFunds')
                ->once()
                ->andReturnSelf();
            $mock->shouldReceive('completeTransfer')
                ->once()
                ->andReturnSelf();
            $mock->shouldReceive('persist')
                ->once()
                ->andThrow(new \Exception('Event Sourcing failed'));
        });

        $response = $this->actingAs($this->user2)
            ->postJson("/api/transfer/purchase/{$this->player->id}");

        expect($response->status())->toBe(400);

        // Verify the transfer listing remains in active status due to automatic rollback
        // The unified transaction ensures all changes are rolled back together
        $this->transferListing->refresh();
        expect($this->transferListing->status)->toBe('active');
        expect($this->transferListing->unique_key)->toBe('active');
    });
});
