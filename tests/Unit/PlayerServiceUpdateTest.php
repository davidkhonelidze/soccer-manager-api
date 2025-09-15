<?php

use App\Models\Country;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use App\Repositories\Interfaces\PlayerRepositoryInterface;
use App\Services\PlayerService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

describe('PlayerService updatePlayer method', function () {
    beforeEach(function () {
        $this->playerRepository = Mockery::mock(PlayerRepositoryInterface::class);
        $this->playerService = new PlayerService($this->playerRepository);

        $this->user = User::factory()->create();
        $this->team = Team::factory()->create();
        $this->user->update(['team_id' => $this->team->id]);

        $this->otherUser = User::factory()->create();
        $this->otherTeam = Team::factory()->create();
        $this->otherUser->update(['team_id' => $this->otherTeam->id]);

        $this->country = Country::first();
        $this->otherCountry = Country::skip(1)->first();

        $this->player = Player::factory()->create([
            'team_id' => $this->team->id,
            'country_id' => $this->country->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->otherPlayer = Player::factory()->create([
            'team_id' => $this->otherTeam->id,
            'country_id' => $this->country->id,
        ]);
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('Successful Updates', function () {
        it('successfully updates player when user owns the player', function () {
            $updateData = [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'country_id' => $this->otherCountry->id,
            ];

            $updatedPlayer = new Player([
                'id' => $this->player->id,
                'team_id' => $this->team->id,
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'country_id' => $this->otherCountry->id,
                'date_of_birth' => $this->player->date_of_birth,
                'position' => $this->player->position,
                'value' => $this->player->value,
            ]);

            // Ensure the id property is accessible
            $updatedPlayer->id = $this->player->id;

            $this->playerRepository
                ->shouldReceive('find')
                ->with($this->player->id)
                ->once()
                ->andReturn($this->player);

            $this->playerRepository
                ->shouldReceive('update')
                ->with($this->player->id, $updateData)
                ->once()
                ->andReturn(true);

            $this->playerRepository
                ->shouldReceive('find')
                ->with($this->player->id)
                ->once()
                ->andReturn($updatedPlayer);

            $result = $this->playerService->updatePlayer(
                $this->player->id,
                $this->team->id,
                $updateData
            );

            expect($result)->toBeInstanceOf(Player::class);
            expect($result->id)->toBe($this->player->id);
            expect($result->first_name)->toBe('Jane');
            expect($result->last_name)->toBe('Smith');
            expect($result->country_id)->toBe($this->otherCountry->id);
        });

        it('returns updated player with all fields', function () {
            $updateData = [
                'first_name' => 'Updated',
                'last_name' => 'Player',
                'country_id' => $this->otherCountry->id,
            ];

            $updatedPlayer = new Player([
                'id' => $this->player->id,
                'team_id' => $this->team->id,
                'first_name' => 'Updated',
                'last_name' => 'Player',
                'country_id' => $this->otherCountry->id,
                'date_of_birth' => $this->player->date_of_birth,
                'position' => $this->player->position,
                'value' => $this->player->value,
            ]);

            // Ensure the id property is accessible
            $updatedPlayer->id = $this->player->id;

            $this->playerRepository
                ->shouldReceive('find')
                ->with($this->player->id)
                ->twice()
                ->andReturn($this->player, $updatedPlayer);

            $this->playerRepository
                ->shouldReceive('update')
                ->with($this->player->id, $updateData)
                ->once()
                ->andReturn(true);

            $result = $this->playerService->updatePlayer(
                $this->player->id,
                $this->team->id,
                $updateData
            );

            expect($result->id)->toEqual($updatedPlayer->id);
            expect($result->first_name)->toEqual($updatedPlayer->first_name);
            expect($result->last_name)->toEqual($updatedPlayer->last_name);
            expect($result->country_id)->toEqual($updatedPlayer->country_id);
            expect($result->team_id)->toEqual($updatedPlayer->team_id);
            expect($result->date_of_birth)->toEqual($updatedPlayer->date_of_birth);
            expect($result->position)->toEqual($updatedPlayer->position);
            expect($result->value)->toEqual($updatedPlayer->value);
        });
    });

    describe('Error Handling', function () {
        it('throws exception when player is not found', function () {
            $updateData = [
                'first_name' => 'Test',
                'last_name' => 'Player',
                'country_id' => $this->country->id,
            ];

            $this->playerRepository
                ->shouldReceive('find')
                ->with(99999)
                ->once()
                ->andReturn(null);

            expect(function () use ($updateData) {
                $this->playerService->updatePlayer(99999, $this->team->id, $updateData);
            })->toThrow(\Exception::class, 'Player not found.');
        });

        it('throws exception when user does not own the player', function () {
            $updateData = [
                'first_name' => 'Hacker',
                'last_name' => 'Attempt',
                'country_id' => $this->country->id,
            ];

            $this->playerRepository
                ->shouldReceive('find')
                ->with($this->otherPlayer->id)
                ->once()
                ->andReturn($this->otherPlayer);

            expect(function () use ($updateData) {
                $this->playerService->updatePlayer(
                    $this->otherPlayer->id,
                    $this->team->id,
                    $updateData
                );
            })->toThrow(\Exception::class, 'You can only update players from your own team.');
        });

        it('throws exception when update fails', function () {
            $updateData = [
                'first_name' => 'Test',
                'last_name' => 'Player',
                'country_id' => $this->country->id,
            ];

            $this->playerRepository
                ->shouldReceive('find')
                ->with($this->player->id)
                ->once()
                ->andReturn($this->player);

            $this->playerRepository
                ->shouldReceive('update')
                ->with($this->player->id, $updateData)
                ->once()
                ->andReturn(false);

            expect(function () use ($updateData) {
                $this->playerService->updatePlayer(
                    $this->player->id,
                    $this->team->id,
                    $updateData
                );
            })->toThrow(\Exception::class, 'Failed to update player.');
        });

        it('handles repository exceptions during find', function () {
            $updateData = [
                'first_name' => 'Test',
                'last_name' => 'Player',
                'country_id' => $this->country->id,
            ];

            $this->playerRepository
                ->shouldReceive('find')
                ->with($this->player->id)
                ->once()
                ->andThrow(new \Exception('Database connection failed'));

            expect(function () use ($updateData) {
                $this->playerService->updatePlayer(
                    $this->player->id,
                    $this->team->id,
                    $updateData
                );
            })->toThrow(\Exception::class, 'Database connection failed');
        });

        it('handles repository exceptions during update', function () {
            $updateData = [
                'first_name' => 'Test',
                'last_name' => 'Player',
                'country_id' => $this->country->id,
            ];

            $this->playerRepository
                ->shouldReceive('find')
                ->with($this->player->id)
                ->once()
                ->andReturn($this->player);

            $this->playerRepository
                ->shouldReceive('update')
                ->with($this->player->id, $updateData)
                ->once()
                ->andThrow(new \Exception('Update failed'));

            expect(function () use ($updateData) {
                $this->playerService->updatePlayer(
                    $this->player->id,
                    $this->team->id,
                    $updateData
                );
            })->toThrow(\Exception::class, 'Update failed');
        });
    });

    describe('Business Logic Validation', function () {
        it('validates team ownership correctly', function () {
            $updateData = [
                'first_name' => 'Test',
                'last_name' => 'Player',
                'country_id' => $this->country->id,
            ];

            // Player belongs to other team
            $this->playerRepository
                ->shouldReceive('find')
                ->with($this->otherPlayer->id)
                ->once()
                ->andReturn($this->otherPlayer);

            expect(function () use ($updateData) {
                $this->playerService->updatePlayer(
                    $this->otherPlayer->id,
                    $this->team->id, // Different team ID
                    $updateData
                );
            })->toThrow(\Exception::class, 'You can only update players from your own team.');
        });

        it('handles edge case where player team_id is null', function () {
            $playerWithoutTeam = new Player([
                'id' => 999,
                'team_id' => null,
                'first_name' => 'Test',
                'last_name' => 'Player',
                'country_id' => $this->country->id,
                'date_of_birth' => '1990-01-01',
                'position' => 'attacker',
                'value' => 1000000.00,
            ]);

            // Ensure the id property is accessible
            $playerWithoutTeam->id = 999;

            $updateData = [
                'first_name' => 'Test',
                'last_name' => 'Player',
                'country_id' => $this->country->id,
            ];

            $this->playerRepository
                ->shouldReceive('find')
                ->with($playerWithoutTeam->id)
                ->once()
                ->andReturn($playerWithoutTeam);

            expect(function () use ($updateData, $playerWithoutTeam) {
                $this->playerService->updatePlayer(
                    $playerWithoutTeam->id,
                    $this->team->id,
                    $updateData
                );
            })->toThrow(\Exception::class, 'You can only update players from your own team.');
        });

        it('handles edge case where team_id is 0', function () {
            $updateData = [
                'first_name' => 'Test',
                'last_name' => 'Player',
                'country_id' => $this->country->id,
            ];

            $this->playerRepository
                ->shouldReceive('find')
                ->with($this->player->id)
                ->once()
                ->andReturn($this->player);

            expect(function () use ($updateData) {
                $this->playerService->updatePlayer(
                    $this->player->id,
                    0, // Invalid team ID
                    $updateData
                );
            })->toThrow(\Exception::class, 'You can only update players from your own team.');
        });
    });
});
