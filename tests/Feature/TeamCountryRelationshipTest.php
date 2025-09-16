<?php

use App\Models\Country;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

describe('Team Country Relationship', function () {
    it('has a country relationship', function () {
        $country = Country::first();
        $team = Team::factory()->create(['country_id' => $country->id]);

        expect($team->country)->not->toBeNull();
        expect($team->country->id)->toBe($country->id);
        expect($team->country->name)->toBe($country->name);
    });

    it('can load team with country relationship', function () {
        $country = Country::first();
        $team = Team::factory()->create(['country_id' => $country->id]);

        $teamWithCountry = Team::with('country')->find($team->id);

        expect($teamWithCountry->country)->not->toBeNull();
        expect($teamWithCountry->country->id)->toBe($country->id);
    });

    it('can load team with country and players sum', function () {
        $country = Country::first();
        $team = Team::factory()->create(['country_id' => $country->id]);

        $teamWithData = Team::with('country')
            ->withSum('players', 'value')
            ->find($team->id);

        expect($teamWithData->country)->not->toBeNull();
        expect($teamWithData->players_sum_value)->toBeNull(); // withSum returns null when no players
    });

    it('converts null players_sum_value to 0 in UserResource', function () {
        $country = Country::first();
        $team = Team::factory()->create(['country_id' => $country->id]);
        $user = \App\Models\User::factory()->create(['team_id' => $team->id]);

        // Load user with team data
        $user->load([
            'team' => function ($query) {
                $query->with('country')
                    ->withSum('players', 'value');
            },
        ]);

        // Create UserResource and get the array
        $resource = new \App\Http\Resources\UserResource($user);
        $data = $resource->toArray(request());

        // Verify that null is converted to 0.0 in the resource
        expect($user->team->players_sum_value)->toBeNull(); // Raw value is null
        expect($data['team']['value'])->toEqual(0.0); // Resource converts to 0.0
    });
});
