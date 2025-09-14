<?php

return [
    'team' => [
        'positions' => [
            'goalkeeper' => [
                'default_count' => 3,
            ],
            'defender' => [
                'default_count' => 6,
            ],
            'midfielder' => [
                'default_count' => 6,
            ],
            'attacker' => [
                'default_count' => 5,
            ],
        ],
        'initial_balance' => env('SOCCER_TEAM_INITIAL_BALANCE', 5000000),
    ],
    'player' => [
        'initial_value' => env('SOCCER_PLAYER_INITIAL_VALUE', 1000000),
    ],
];
