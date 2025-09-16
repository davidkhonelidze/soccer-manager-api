<?php

use App\Enums\TransferStatus;
use App\Models\Player;
use App\Models\Team;
use App\Models\TransferListing;
use App\Models\User;
use App\Projectors\TransferProjector;
use App\StorableEvents\PlayerTransferCompleted;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();

    // Create test data
    $this->user1 = User::factory()->create();
    $this->user2 = User::factory()->create();

    $this->team1 = Team::factory()->create();
    $this->team2 = Team::factory()->create();

    $this->user1->update(['team_id' => $this->team1->id]);
    $this->user2->update(['team_id' => $this->team2->id]);

    $this->player = Player::factory()->create([
        'team_id' => $this->team1->id,
        'value' => 1000000.00, // Set a specific value for testing
    ]);

    // Create transfer listing
    $this->transferListing = TransferListing::create([
        'player_id' => $this->player->id,
        'selling_team_id' => $this->team1->id,
        'asking_price' => 1500000,
        'status' => TransferStatus::ACTIVE,
        'unique_key' => TransferStatus::ACTIVE->value,
    ]);

    // Set team balances
    $this->team1->update(['balance' => 5000000]);
    $this->team2->update(['balance' => 5000000]);

    $this->projector = new TransferProjector();
});

describe('Player Value Increase on Transfer', function () {
    it('increases player value by random percentage between 10% and 100%', function () {
        $originalValue = $this->player->value;

        // Create and handle the transfer completion event
        $event = new PlayerTransferCompleted(
            $this->player->id,
            $this->team2->uuid
        );

        $this->projector->onPlayerTransferCompleted($event);

        // Refresh player from database
        $this->player->refresh();

        // Verify value increased
        expect($this->player->value)->toBeGreaterThan($originalValue);

        // Verify value is within expected range (10% to 100% increase)
        $minExpectedValue = $originalValue * 1.10; // 10% increase
        $maxExpectedValue = $originalValue * 2.00; // 100% increase

        expect($this->player->value)->toBeGreaterThanOrEqual($minExpectedValue);
        expect($this->player->value)->toBeLessThanOrEqual($maxExpectedValue);

        // Verify team assignment was updated
        expect($this->player->team_id)->toBe($this->team2->id);
    });

    it('updates transfer listing status to sold', function () {
        $event = new PlayerTransferCompleted(
            $this->player->id,
            $this->team2->uuid
        );

        $this->projector->onPlayerTransferCompleted($event);

        // Verify transfer listing status
        $this->transferListing->refresh();
        expect($this->transferListing->status)->toBe(TransferStatus::SOLD);
        expect($this->transferListing->unique_key)->toBeNull();
    });

    it('handles multiple transfers with different value increases', function () {
        $originalValue = $this->player->value;
        $values = [];

        // Simulate multiple transfers to see different value increases
        for ($i = 0; $i < 5; $i++) {
            // Create a new team for each transfer
            $newTeam = Team::factory()->create();

            $event = new PlayerTransferCompleted(
                $this->player->id,
                $newTeam->uuid
            );

            $this->projector->onPlayerTransferCompleted($event);

            $this->player->refresh();
            $values[] = $this->player->value;
        }

        // Verify all values are different (random increases)
        expect($values)->toHaveCount(5);
        expect($values)->toHaveCount(count(array_unique($values)));

        // Verify all values are greater than original
        foreach ($values as $value) {
            expect($value)->toBeGreaterThan($originalValue);
        }
    });

    it('respects configuration for value increase range', function () {
        // Test with custom configuration
        config(['soccer.player.value_increase.min_percentage' => 20]);
        config(['soccer.player.value_increase.max_percentage' => 50]);

        $originalValue = $this->player->value;

        $event = new PlayerTransferCompleted(
            $this->player->id,
            $this->team2->uuid
        );

        $this->projector->onPlayerTransferCompleted($event);

        $this->player->refresh();

        // Verify value is within custom range (20% to 50% increase)
        $minExpectedValue = $originalValue * 1.20; // 20% increase
        $maxExpectedValue = $originalValue * 1.50; // 50% increase

        expect($this->player->value)->toBeGreaterThanOrEqual($minExpectedValue);
        expect($this->player->value)->toBeLessThanOrEqual($maxExpectedValue);
    });

    it('handles edge case where player is not found', function () {
        $nonExistentPlayerId = 99999;

        $event = new PlayerTransferCompleted(
            $nonExistentPlayerId,
            $this->team2->uuid
        );

        // This should not throw an exception
        expect(function () use ($event) {
            $this->projector->onPlayerTransferCompleted($event);
        })->not->toThrow(\Exception::class);
    });

    it('handles edge case where team is not found', function () {
        $nonExistentTeamUuid = 'non-existent-uuid';

        $event = new PlayerTransferCompleted(
            $this->player->id,
            $nonExistentTeamUuid
        );

        $originalValue = $this->player->value;

        // This should not throw an exception and should not change the player
        expect(function () use ($event) {
            $this->projector->onPlayerTransferCompleted($event);
        })->not->toThrow(\Exception::class);

        $this->player->refresh();
        expect($this->player->value)->toBe($originalValue);
        expect($this->player->team_id)->toBe($this->team1->id); // Should remain unchanged
    });
});
