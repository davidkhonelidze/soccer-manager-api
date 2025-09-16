<?php

use App\Enums\TransferStatus;
use App\Models\Country;
use App\Models\Player;
use App\Models\Team;
use App\Models\TransferListing;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();

    $this->country = Country::first();
    $this->team = Team::factory()->create(['country_id' => $this->country->id]);
    $this->player = Player::factory()->create(['team_id' => $this->team->id]);
});

describe('TransferStatus Enum', function () {
    it('has all required status values', function () {
        $expectedStatuses = ['active', 'processing', 'sold', 'canceled'];
        $actualStatuses = TransferStatus::values();

        expect($actualStatuses)->toEqual($expectedStatuses);
    });

    it('provides correct available for purchase statuses', function () {
        $availableStatuses = TransferStatus::availableForPurchase();

        expect($availableStatuses)->toEqual(['active']);
    });

    it('provides correct in progress statuses', function () {
        $inProgressStatuses = TransferStatus::inProgress();

        expect($inProgressStatuses)->toEqual(['active', 'processing']);
    });

    it('provides correct completed statuses', function () {
        $completedStatuses = TransferStatus::completed();

        expect($completedStatuses)->toEqual(['sold', 'canceled']);
    });

    it('provides correct status labels', function () {
        expect(TransferStatus::ACTIVE->label())->toBe('Active');
        expect(TransferStatus::PROCESSING->label())->toBe('Processing');
        expect(TransferStatus::SOLD->label())->toBe('Sold');
        expect(TransferStatus::CANCELED->label())->toBe('Canceled');
    });

    it('provides correct status descriptions', function () {
        expect(TransferStatus::ACTIVE->description())->toContain('active and available for purchase');
        expect(TransferStatus::PROCESSING->description())->toContain('being processed');
        expect(TransferStatus::SOLD->description())->toContain('completed successfully');
        expect(TransferStatus::CANCELED->description())->toContain('canceled');
    });

    it('correctly identifies status availability for purchase', function () {
        expect(TransferStatus::ACTIVE->isAvailableForPurchase())->toBeTrue();
        expect(TransferStatus::PROCESSING->isAvailableForPurchase())->toBeFalse();
        expect(TransferStatus::SOLD->isAvailableForPurchase())->toBeFalse();
        expect(TransferStatus::CANCELED->isAvailableForPurchase())->toBeFalse();
    });

    it('correctly identifies status as in progress', function () {
        expect(TransferStatus::ACTIVE->isInProgress())->toBeTrue();
        expect(TransferStatus::PROCESSING->isInProgress())->toBeTrue();
        expect(TransferStatus::SOLD->isInProgress())->toBeFalse();
        expect(TransferStatus::CANCELED->isInProgress())->toBeFalse();
    });

    it('correctly identifies status as completed', function () {
        expect(TransferStatus::ACTIVE->isCompleted())->toBeFalse();
        expect(TransferStatus::PROCESSING->isCompleted())->toBeFalse();
        expect(TransferStatus::SOLD->isCompleted())->toBeTrue();
        expect(TransferStatus::CANCELED->isCompleted())->toBeTrue();
    });
});

describe('TransferStatus with TransferListing Model', function () {
    it('casts status to enum in model', function () {
        $transferListing = TransferListing::create([
            'player_id' => $this->player->id,
            'selling_team_id' => $this->team->id,
            'asking_price' => 1000000.00,
            'status' => TransferStatus::ACTIVE,
            'unique_key' => 'active',
        ]);

        expect($transferListing->status)->toBeInstanceOf(TransferStatus::class);
        expect($transferListing->status)->toBe(TransferStatus::ACTIVE);
    });

    it('can query by enum status', function () {
        TransferListing::create([
            'player_id' => $this->player->id,
            'selling_team_id' => $this->team->id,
            'asking_price' => 1000000.00,
            'status' => TransferStatus::ACTIVE,
            'unique_key' => 'active',
        ]);

        $activeListings = TransferListing::where('status', TransferStatus::ACTIVE)->get();

        expect($activeListings)->toHaveCount(1);
        expect($activeListings->first()->status)->toBe(TransferStatus::ACTIVE);
    });

    it('can query by multiple enum statuses', function () {
        TransferListing::create([
            'player_id' => $this->player->id,
            'selling_team_id' => $this->team->id,
            'asking_price' => 1000000.00,
            'status' => TransferStatus::ACTIVE,
            'unique_key' => 'active',
        ]);

        $inProgressListings = TransferListing::whereIn('status', TransferStatus::inProgress())->get();

        expect($inProgressListings)->toHaveCount(1);
        expect($inProgressListings->first()->status)->toBe(TransferStatus::ACTIVE);
    });

    it('can update status using enum', function () {
        $transferListing = TransferListing::create([
            'player_id' => $this->player->id,
            'selling_team_id' => $this->team->id,
            'asking_price' => 1000000.00,
            'status' => TransferStatus::ACTIVE,
            'unique_key' => 'active',
        ]);

        $transferListing->update(['status' => TransferStatus::PROCESSING]);

        expect($transferListing->fresh()->status)->toBe(TransferStatus::PROCESSING);
    });
});
