<?php

use App\Models\Country;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use App\Repositories\Interfaces\PlayerRepositoryInterface;
use App\Services\Interfaces\UserServiceInterface;
use App\Services\PlayerAuthorizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

describe('PlayerAuthorizationService', function () {
    beforeEach(function () {
        $this->playerRepository = Mockery::mock(PlayerRepositoryInterface::class);
        $this->userService = Mockery::mock(UserServiceInterface::class);
        $this->authorizationService = new PlayerAuthorizationService($this->playerRepository, $this->userService);

        $this->user = User::factory()->create();
        $this->team = Team::factory()->create();
        $this->user->update(['team_id' => $this->team->id]);

        $this->otherUser = User::factory()->create();
        $this->otherTeam = Team::factory()->create();
        $this->otherUser->update(['team_id' => $this->otherTeam->id]);

        $this->country = Country::first();

        $this->player = Player::factory()->create([
            'team_id' => $this->team->id,
            'country_id' => $this->country->id,
        ]);

        $this->otherPlayer = Player::factory()->create([
            'team_id' => $this->otherTeam->id,
            'country_id' => $this->country->id,
        ]);
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('canUserUpdatePlayer', function () {
        it('returns true when user owns the player', function () {
            $this->userService
                ->shouldReceive('find')
                ->with($this->user->id)
                ->once()
                ->andReturn($this->user);

            $this->playerRepository
                ->shouldReceive('find')
                ->with($this->player->id)
                ->once()
                ->andReturn($this->player);

            $result = $this->authorizationService->canUserUpdatePlayer(
                $this->user->id,
                $this->player->id
            );

            expect($result)->toBeTrue();
        });

        it('returns false when user does not own the player', function () {
            $this->userService
                ->shouldReceive('find')
                ->with($this->otherUser->id)
                ->once()
                ->andReturn($this->otherUser);

            $this->playerRepository
                ->shouldReceive('find')
                ->with($this->player->id)
                ->once()
                ->andReturn($this->player);

            $result = $this->authorizationService->canUserUpdatePlayer(
                $this->otherUser->id,
                $this->player->id
            );

            expect($result)->toBeFalse();
        });

        it('returns false when player does not exist', function () {
            $this->userService
                ->shouldReceive('find')
                ->with($this->user->id)
                ->once()
                ->andReturn($this->user);

            $this->playerRepository
                ->shouldReceive('find')
                ->with(99999)
                ->once()
                ->andReturn(null);

            $result = $this->authorizationService->canUserUpdatePlayer(
                $this->user->id,
                99999
            );

            expect($result)->toBeFalse();
        });

        it('returns false when user does not exist', function () {
            $this->userService
                ->shouldReceive('find')
                ->with(99999)
                ->once()
                ->andReturn(null);

            $result = $this->authorizationService->canUserUpdatePlayer(
                99999,
                $this->player->id
            );

            expect($result)->toBeFalse();
        });

        it('returns false when user has no team', function () {
            $userWithoutTeam = User::factory()->create(['team_id' => null]);

            $this->userService
                ->shouldReceive('find')
                ->with($userWithoutTeam->id)
                ->once()
                ->andReturn($userWithoutTeam);

            $result = $this->authorizationService->canUserUpdatePlayer(
                $userWithoutTeam->id,
                $this->player->id
            );

            expect($result)->toBeFalse();
        });

        it('returns false when user team_id is null', function () {
            $userWithNullTeam = User::factory()->create();
            $userWithNullTeam->update(['team_id' => null]);

            $this->userService
                ->shouldReceive('find')
                ->with($userWithNullTeam->id)
                ->once()
                ->andReturn($userWithNullTeam);

            $result = $this->authorizationService->canUserUpdatePlayer(
                $userWithNullTeam->id,
                $this->player->id
            );

            expect($result)->toBeFalse();
        });

        it('handles edge case where player team_id is null', function () {
            $playerId = 999;
            $playerWithoutTeam = new Player([
                'id' => $playerId,
                'team_id' => null,
                'first_name' => 'Test',
                'last_name' => 'Player',
                'country_id' => $this->country->id,
                'date_of_birth' => '1990-01-01',
                'position' => 'attacker',
                'value' => 1000000.00,
            ]);

            $this->userService
                ->shouldReceive('find')
                ->with($this->user->id)
                ->once()
                ->andReturn($this->user);

            $this->playerRepository
                ->shouldReceive('find')
                ->with($playerId)
                ->once()
                ->andReturn($playerWithoutTeam);

            $result = $this->authorizationService->canUserUpdatePlayer(
                $this->user->id,
                $playerId
            );

            expect($result)->toBeFalse();
        });

        it('handles edge case where user team_id matches but player team_id is different', function () {
            $playerId = 998;
            $playerWithDifferentTeam = new Player([
                'id' => $playerId,
                'team_id' => $this->otherTeam->id,
                'first_name' => 'Different',
                'last_name' => 'Player',
                'country_id' => $this->country->id,
                'date_of_birth' => '1990-01-01',
                'position' => 'attacker',
                'value' => 1000000.00,
            ]);

            $this->userService
                ->shouldReceive('find')
                ->with($this->user->id)
                ->once()
                ->andReturn($this->user);

            $this->playerRepository
                ->shouldReceive('find')
                ->with($playerId)
                ->once()
                ->andReturn($playerWithDifferentTeam);

            $result = $this->authorizationService->canUserUpdatePlayer(
                $this->user->id,
                $playerId
            );

            expect($result)->toBeFalse();
        });
    });

    describe('Integration with Repository', function () {
        it('calls repository find method with correct parameters', function () {
            $this->userService
                ->shouldReceive('find')
                ->with($this->user->id)
                ->once()
                ->andReturn($this->user);

            $this->playerRepository
                ->shouldReceive('find')
                ->with($this->player->id)
                ->once()
                ->andReturn($this->player);

            $this->authorizationService->canUserUpdatePlayer(
                $this->user->id,
                $this->player->id
            );

            // The expectation is verified by Mockery
            expect(true)->toBeTrue();
        });

        it('handles repository exceptions gracefully', function () {
            $this->userService
                ->shouldReceive('find')
                ->with($this->user->id)
                ->once()
                ->andReturn($this->user);

            $this->playerRepository
                ->shouldReceive('find')
                ->with($this->player->id)
                ->once()
                ->andThrow(new \Exception('Database error'));

            expect(function () {
                $this->authorizationService->canUserUpdatePlayer(
                    $this->user->id,
                    $this->player->id
                );
            })->toThrow(\Exception::class, 'Database error');
        });
    });
});
