<?php

use App\Models\Team;
use App\Models\User;
use App\Services\TeamAuthorizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();

    $this->service = new TeamAuthorizationService();

    // Create test users and teams
    $this->user1 = User::factory()->create();
    $this->user2 = User::factory()->create();
    $this->userWithoutTeam = User::factory()->create(['team_id' => null]);

    $this->team1 = Team::factory()->create();
    $this->team2 = Team::factory()->create();

    $this->user1->update(['team_id' => $this->team1->id]);
    $this->user2->update(['team_id' => $this->team2->id]);
});

describe('TeamAuthorizationService', function () {
    describe('canUserUpdateTeam', function () {
        it('returns true when user owns the team', function () {
            $result = $this->service->canUserUpdateTeam($this->user1->id, $this->team1->id);

            expect($result)->toBeTrue();
        });

        it('returns false when user does not own the team', function () {
            $result = $this->service->canUserUpdateTeam($this->user1->id, $this->team2->id);

            expect($result)->toBeFalse();
        });

        it('returns false when user does not exist', function () {
            $result = $this->service->canUserUpdateTeam(99999, $this->team1->id);

            expect($result)->toBeFalse();
        });

        it('returns false when team does not exist', function () {
            $result = $this->service->canUserUpdateTeam($this->user1->id, 99999);

            expect($result)->toBeFalse();
        });

        it('returns false when user has no team assigned', function () {
            $result = $this->service->canUserUpdateTeam($this->userWithoutTeam->id, $this->team1->id);

            expect($result)->toBeFalse();
        });

        it('returns false when user team_id is null', function () {
            $userWithNullTeam = User::factory()->create(['team_id' => null]);

            $result = $this->service->canUserUpdateTeam($userWithNullTeam->id, $this->team1->id);

            expect($result)->toBeFalse();
        });
    });

    describe('userHasTeam', function () {
        it('returns true when user has a team assigned', function () {
            $result = $this->service->userHasTeam($this->user1->id);

            expect($result)->toBeTrue();
        });

        it('returns false when user has no team assigned', function () {
            $result = $this->service->userHasTeam($this->userWithoutTeam->id);

            expect($result)->toBeFalse();
        });

        it('returns false when user does not exist', function () {
            $result = $this->service->userHasTeam(99999);

            expect($result)->toBeFalse();
        });

        it('returns false when user team_id is null', function () {
            $userWithNullTeam = User::factory()->create(['team_id' => null]);

            $result = $this->service->userHasTeam($userWithNullTeam->id);

            expect($result)->toBeFalse();
        });
    });

    describe('getUserTeamId', function () {
        it('returns team ID when user has a team assigned', function () {
            $result = $this->service->getUserTeamId($this->user1->id);

            expect($result)->toBe($this->team1->id);
        });

        it('returns null when user has no team assigned', function () {
            $result = $this->service->getUserTeamId($this->userWithoutTeam->id);

            expect($result)->toBeNull();
        });

        it('returns null when user does not exist', function () {
            $result = $this->service->getUserTeamId(99999);

            expect($result)->toBeNull();
        });

        it('returns null when user team_id is null', function () {
            $userWithNullTeam = User::factory()->create(['team_id' => null]);

            $result = $this->service->getUserTeamId($userWithNullTeam->id);

            expect($result)->toBeNull();
        });
    });
});
