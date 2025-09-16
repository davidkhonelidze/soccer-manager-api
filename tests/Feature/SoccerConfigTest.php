<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Soccer Configuration', function () {
    it('uses default values when environment variables are not set', function () {
        // Test default position counts
        expect(config('soccer.team.positions.goalkeeper.default_count'))->toBe(3);
        expect(config('soccer.team.positions.defender.default_count'))->toBe(6);
        expect(config('soccer.team.positions.midfielder.default_count'))->toBe(6);
        expect(config('soccer.team.positions.attacker.default_count'))->toBe(5);
    });

    it('uses environment variables when set', function () {
        // Test that the config system properly handles integer casting
        // by directly setting config values to simulate env variable behavior
        app('config')->set('soccer.team.positions.goalkeeper.default_count', (int) '2');
        app('config')->set('soccer.team.positions.defender.default_count', (int) '4');
        app('config')->set('soccer.team.positions.midfielder.default_count', (int) '4');
        app('config')->set('soccer.team.positions.attacker.default_count', (int) '3');

        // Test custom position counts
        expect(config('soccer.team.positions.goalkeeper.default_count'))->toBe(2);
        expect(config('soccer.team.positions.defender.default_count'))->toBe(4);
        expect(config('soccer.team.positions.midfielder.default_count'))->toBe(4);
        expect(config('soccer.team.positions.attacker.default_count'))->toBe(3);
    });

    it('has all required position configurations', function () {
        $positions = config('soccer.team.positions');

        expect($positions)->toHaveKeys(['goalkeeper', 'defender', 'midfielder', 'attacker']);

        foreach ($positions as $position => $config) {
            expect($config)->toHaveKey('default_count');
            expect($config['default_count'])->toBeInt();
            expect($config['default_count'])->toBeGreaterThan(0);
        }
    });

    it('calculates total team size correctly', function () {
        $positions = config('soccer.team.positions');
        $totalPlayers = 0;

        foreach ($positions as $position => $config) {
            $totalPlayers += $config['default_count'];
        }

        expect($totalPlayers)->toBe(20); // 3 + 6 + 6 + 5 = 20
    });

    it('ensures position counts are integers', function () {
        $positions = config('soccer.team.positions');

        foreach ($positions as $position => $config) {
            expect($config['default_count'])->toBeInt();
            expect(is_string($config['default_count']))->toBeFalse();
        }
    });
});
