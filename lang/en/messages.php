<?php

return [
    'registration' => [
        'success' => 'User registered successfully',
        'failed' => 'Registration failed',
    ],
    'login' => [
        'success' => 'Login successful',
        'invalid_credentials' => 'Invalid credentials',
    ],
    'auth' => [
        'unauthenticated' => 'Unauthenticated',
    ],
    'transfer' => [
        'listed_successfully' => 'Player listed for transfer successfully',
        'player_not_found' => 'Player not found or does not belong to your team',
        'no_team' => 'You must be assigned to a team to list players for transfer',
        'purchase_successful' => 'Player purchased successfully',
        'team_not_found' => 'Team not found',
    ],
    'player' => [
        'updated_successfully' => 'Player updated successfully',
        'not_found' => 'Player not found',
        'no_team' => 'You must be assigned to a team to update players',
        'not_owned' => 'You can only update players from your own team',
        'update_failed' => 'Failed to update player',
        'list_retrieved_successfully' => 'Players retrieved successfully',
        'list_failed' => 'Failed to retrieve players',
    ],
    'team' => [
        'updated_successfully' => 'Team updated successfully',
        'not_found' => 'Team not found',
        'no_team' => 'You must be assigned to a team to update team information',
        'not_owned' => 'You can only update your own team',
        'update_failed' => 'Failed to update team',
    ],
    'user' => [
        'info_retrieved_successfully' => 'User information retrieved successfully',
    ],
    'general' => [
        'error' => 'Internal server error',
    ],
];
