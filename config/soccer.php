<?php

return [
    'team' => [
        'positions' => [
            'goalkeeper' => [
                'default_count' => (int) env('SOCCER_POSITION_GOALKEEPER_COUNT', 3),
            ],
            'defender' => [
                'default_count' => (int) env('SOCCER_POSITION_DEFENDER_COUNT', 6),
            ],
            'midfielder' => [
                'default_count' => (int) env('SOCCER_POSITION_MIDFIELDER_COUNT', 6),
            ],
            'attacker' => [
                'default_count' => (int) env('SOCCER_POSITION_ATTACKER_COUNT', 5),
            ],
        ],
        'initial_balance' => env('SOCCER_TEAM_INITIAL_BALANCE', 5000000),
    ],
    'player' => [
        'initial_value' => env('SOCCER_PLAYER_INITIAL_VALUE', 1000000),
        'value_increase' => [
            'min_percentage' => env('SOCCER_PLAYER_VALUE_INCREASE_MIN', 10),
            'max_percentage' => env('SOCCER_PLAYER_VALUE_INCREASE_MAX', 100),
        ],
    ],
    'pagination' => [
        'transfer_listings_per_page' => env('SOCCER_TRANSFER_LISTINGS_PER_PAGE', 15),
        'players_per_page' => env('SOCCER_PLAYERS_PER_PAGE', 20),
        'max_per_page' => env('SOCCER_MAX_PER_PAGE', 100),
    ],
];
