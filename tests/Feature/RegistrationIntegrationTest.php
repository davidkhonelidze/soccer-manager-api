<?php

use App\Models\User;
use App\Models\Team;
use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);
beforeEach(function () {
    $this->seed();
});

describe('Registration Integration Tests', function () {
    it('completes full registration flow with valid data', function () {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'MyP@ssw0rd123',
            'password_confirmation' => 'MyP@ssw0rd123',
        ];

        $response = $this->postJson('/api/register', $userData);

        // Assert response structure and status
        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'created_at',
                    ],
                ],
            ]);

        // Assert response content
        $responseData = $response->json();
        expect($responseData['message'])->toBe('User registered successfully');
        expect($responseData['data']['user']['name'])->toBe('John Doe');
        expect($responseData['data']['user']['email'])->toBe('john.doe@example.com');

        // Assert database state
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
        ]);

        // Assert password is properly hashed
        $user = User::where('email', 'john.doe@example.com')->first();
        expect($user)->not->toBeNull();
        expect(Hash::check('MyP@ssw0rd123', $user->password))->toBeTrue();
        expect($user->password)->not->toBe('MyP@ssw0rd123'); // Ensure it's hashed

        // Assert user can be retrieved from database
        expect($user->name)->toBe('John Doe');
        expect($user->email)->toBe('john.doe@example.com');
        expect($user->id)->toBeInt();
        expect($user->created_at)->not->toBeNull();
        expect($user->updated_at)->not->toBeNull();
    });

    it('handles registration with complex password requirements', function () {
        $userData = [
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
            'password' => 'ComplexP@ssw0rd!2024#',
            'password_confirmation' => 'ComplexP@ssw0rd!2024#',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201);

        // Verify user was created with complex password
        $user = User::where('email', 'jane.smith@example.com')->first();
        expect($user)->not->toBeNull();
        expect(Hash::check('ComplexP@ssw0rd!2024#', $user->password))->toBeTrue();
    });

    it('prevents duplicate email registration', function () {
        // Create first user
        User::factory()->create(['email' => 'existing@example.com']);

        $userData = [
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'password' => 'MyP@ssw0rd123',
            'password_confirmation' => 'MyP@ssw0rd123',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Verify only one user exists with this email
        $userCount = User::where('email', 'existing@example.com')->count();
        expect($userCount)->toBe(1);
    });

    it('handles registration with unicode characters', function () {
        $userData = [
            'name' => 'José María García',
            'email' => 'jose.garcia@example.com',
            'password' => 'MyP@ssw0rd123',
            'password_confirmation' => 'MyP@ssw0rd123',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201);

        $user = User::where('email', 'jose.garcia@example.com')->first();
        expect($user)->not->toBeNull();
        expect($user->name)->toBe('José María García');
    });

    it('handles registration with edge case email formats', function () {
        $testEmails = [
            'test+tag@example.com',
            'test.email@example.com',
            'test123@example.com',
            'a@b.co',
        ];

        foreach ($testEmails as $index => $email) {
            $userData = [
                'name' => "User {$index}",
                'email' => $email,
                'password' => 'MyP@ssw0rd123',
                'password_confirmation' => 'MyP@ssw0rd123',
            ];

            $response = $this->postJson('/api/register', $userData);

            $response->assertStatus(201, "Failed for email: {$email}");

            $user = User::where('email', $email)->first();
            expect($user)->not->toBeNull();
            expect($user->email)->toBe($email);
        }
    });

    it('handles registration with maximum length fields', function () {
        $userData = [
            'name' => str_repeat('A', 255),
            'email' => str_repeat('a', 238).'@example.com',
            'password' => 'MyP@ssw0rd123',
            'password_confirmation' => 'MyP@ssw0rd123',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201);

        $user = User::where('email', $userData['email'])->first();
        expect($user)->not->toBeNull();
        expect($user->name)->toBe($userData['name']);
        expect($user->email)->toBe($userData['email']);
    });

    it('handles registration with minimum length fields', function () {
        $userData = [
            'name' => 'Jo',
            'email' => 'a@b.co',
            'password' => 'MyP@ssw0rd123',
            'password_confirmation' => 'MyP@ssw0rd123',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201);

        $user = User::where('email', 'a@b.co')->first();
        expect($user)->not->toBeNull();
        expect($user->name)->toBe('Jo');
        expect($user->email)->toBe('a@b.co');
    });

    it('validates all required fields are present', function () {
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    });

    it('validates password confirmation matches', function () {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'MyP@ssw0rd123',
            'password_confirmation' => 'DifferentPassword123!',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        // Verify no user was created
        $userCount = User::where('email', 'john.doe@example.com')->count();
        expect($userCount)->toBe(0);
    });

    it('validates password meets complexity requirements', function () {
        $weakPasswords = [
            'password',      // No uppercase, numbers, symbols
            'PASSWORD',      // No lowercase, numbers, symbols
            'Password',      // No numbers, symbols
            'Password123',   // No symbols
            'Password!',     // No numbers
            '12345678',      // No letters
            'Pass1!',        // Too short
        ];

        foreach ($weakPasswords as $password) {
            $userData = [
                'name' => 'Test User',
                'email' => 'test'.uniqid().'@example.com',
                'password' => $password,
                'password_confirmation' => $password,
            ];

            $response = $this->postJson('/api/register', $userData);

            $response->assertStatus(422)
                ->assertJson([
                    'errors' => [
                        'password' => [],
                    ],
                ]);
        }
    });

    it('validates email format', function () {
        $invalidEmails = [
            'invalid-email',
            '@example.com',
            'test@',
            'test.example.com',
            'test@.com',
            'test@example.',
        ];

        foreach ($invalidEmails as $email) {
            $userData = [
                'name' => 'Test User',
                'email' => $email,
                'password' => 'MyP@ssw0rd123',
                'password_confirmation' => 'MyP@ssw0rd123',
            ];

            $response = $this->postJson('/api/register', $userData);

            $response->assertStatus(422)
                ->assertJson([
                    'errors' => [
                        'email' => [],
                    ],
                ]);
        }
    });

    it('validates email uniqueness', function () {
        // ჯერ user შექმნა
        User::factory()->create(['email' => 'existing@example.com']);

        $userData = [
            'name' => 'Test User',
            'email' => 'existing@example.com', // duplicate email
            'password' => 'MyP@ssw0rd123',
            'password_confirmation' => 'MyP@ssw0rd123',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
            ->assertJson([
                'errors' => [
                    'email' => [],
                ],
            ]);
    });

    it('handles concurrent registration attempts', function () {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'MyP@ssw0rd123',
            'password_confirmation' => 'MyP@ssw0rd123',
        ];

        // Simulate concurrent requests (in real scenario, these would be parallel)
        $response1 = $this->postJson('/api/register', $userData);
        $response1->assertStatus(201);

        // Second attempt with same email should fail
        $response2 = $this->postJson('/api/register', $userData);
        $response2->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Verify only one user was created
        $userCount = User::where('email', 'john.doe@example.com')->count();
        expect($userCount)->toBe(1);
    });

    it('ensures user data is properly serialized in response', function () {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'MyP@ssw0rd123',
            'password_confirmation' => 'MyP@ssw0rd123',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201);

        $responseData = $response->json();
        $userData = $responseData['data']['user'];

        // Verify all expected fields are present
        expect($userData)->toHaveKey('id');
        expect($userData)->toHaveKey('name');
        expect($userData)->toHaveKey('email');
        expect($userData)->toHaveKey('created_at');

        // Verify sensitive fields are not exposed
        expect($userData)->not->toHaveKey('password');
        expect($userData)->not->toHaveKey('remember_token');
        expect($userData)->not->toHaveKey('email_verified_at');

        // Verify data types
        expect($userData['id'])->toBeInt();
        expect($userData['name'])->toBeString();
        expect($userData['email'])->toBeString();
        expect($userData['created_at'])->toBeString();
    });
});

describe('Team Creation During Registration', function () {
    it('automatically creates a team when a user registers', function () {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'MyP@ssw0rd123',
            'password_confirmation' => 'MyP@ssw0rd123',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201);

        // Verify user was created
        $user = User::where('email', 'john.doe@example.com')->first();
        expect($user)->not->toBeNull();
        expect($user->team_id)->not->toBeNull();

        // Verify team was created
        $team = Team::find($user->team_id);
        expect($team)->not->toBeNull();
        expect($team->uuid)->not->toBeNull();
        expect($team->balance)->toBe(number_format(config('soccer.team.initial_balance', 5000000), 2, '.', ''));

        // Verify team is properly associated with user
        expect($user->team_id)->toBe($team->id);
    });

    it('creates team with correct default properties', function () {
        $userData = [
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
            'password' => 'MyP@ssw0rd123',
            'password_confirmation' => 'MyP@ssw0rd123',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201);

        $user = User::where('email', 'jane.smith@example.com')->first();
        $team = Team::find($user->team_id);

        // Verify team has correct properties
        expect($team->uuid)->toBeString();
        expect($team->balance)->toBe(number_format(config('soccer.team.initial_balance', 5000000), 2, '.', ''));
        expect($team->created_at)->not->toBeNull();
        expect($team->updated_at)->not->toBeNull();

        // Verify team exists in database
        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'uuid' => $team->uuid,
            'balance' => config('soccer.team.initial_balance', 5000000),
        ]);
    });

    it('ensures each user gets a unique team', function () {
        $user1Data = [
            'name' => 'User One',
            'email' => 'user1@example.com',
            'password' => 'MyP@ssw0rd123',
            'password_confirmation' => 'MyP@ssw0rd123',
        ];

        $user2Data = [
            'name' => 'User Two',
            'email' => 'user2@example.com',
            'password' => 'MyP@ssw0rd123',
            'password_confirmation' => 'MyP@ssw0rd123',
        ];

        // Register first user
        $response1 = $this->postJson('/api/register', $user1Data);
        $response1->assertStatus(201);

        // Register second user
        $response2 = $this->postJson('/api/register', $user2Data);
        $response2->assertStatus(201);

        // Verify both users have different teams
        $user1 = User::where('email', 'user1@example.com')->first();
        $user2 = User::where('email', 'user2@example.com')->first();

        expect($user1->team_id)->not->toBe($user2->team_id);

        // Verify both teams exist
        $team1 = Team::find($user1->team_id);
        $team2 = Team::find($user2->team_id);

        expect($team1)->not->toBeNull();
        expect($team2)->not->toBeNull();
        expect($team1->id)->not->toBe($team2->id);
    });
});

describe('Player Generation During Registration', function () {
    it('automatically generates players when a team is created during registration', function () {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'MyP@ssw0rd123',
            'password_confirmation' => 'MyP@ssw0rd123',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201);

        $user = User::where('email', 'john.doe@example.com')->first();
        $team = Team::find($user->team_id);

        // Get expected player counts from config
        $positions = config('soccer.team.positions');
        $expectedTotalPlayers = array_sum(array_column($positions, 'default_count'));

        // Verify correct number of players were created
        $actualPlayerCount = Player::where('team_id', $team->id)->count();
        expect($actualPlayerCount)->toBe($expectedTotalPlayers);

        // Verify players are associated with the team
        $players = Player::where('team_id', $team->id)->get();
        foreach ($players as $player) {
            expect($player->team_id)->toBe($team->id);
        }
    });

    it('generates players for each position according to configuration', function () {
        $userData = [
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
            'password' => 'MyP@ssw0rd123',
            'password_confirmation' => 'MyP@ssw0rd123',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201);

        $user = User::where('email', 'jane.smith@example.com')->first();
        $team = Team::find($user->team_id);

        // Get position configuration
        $positions = config('soccer.team.positions');

        // Verify each position has the correct number of players
        foreach ($positions as $position => $config) {
            $expectedCount = $config['default_count'];
            $actualCount = Player::where('team_id', $team->id)
                ->where('position', $position)
                ->count();

            expect($actualCount)->toBe($expectedCount, "Position {$position} should have {$expectedCount} players, but has {$actualCount}");
        }
    });

    it('ensures all generated players have correct position assignments', function () {
        $userData = [
            'name' => 'Bob Wilson',
            'email' => 'bob.wilson@example.com',
            'password' => 'MyP@ssw0rd123',
            'password_confirmation' => 'MyP@ssw0rd123',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201);

        $user = User::where('email', 'bob.wilson@example.com')->first();
        $team = Team::find($user->team_id);

        $positions = config('soccer.team.positions');
        $players = Player::where('team_id', $team->id)->get();

        // Verify all players have valid positions
        foreach ($players as $player) {
            expect($player->position)->toBeIn(array_keys($positions));
            expect($player->position)->not->toBeNull();
            expect($player->position)->not->toBeEmpty();
        }

        // Verify no players have invalid positions
        $invalidPositionCount = Player::where('team_id', $team->id)
            ->whereNotIn('position', array_keys($positions))
            ->count();
        expect($invalidPositionCount)->toBe(0);
    });

    it('verifies players are properly associated with the created team', function () {
        $userData = [
            'name' => 'Alice Johnson',
            'email' => 'alice.johnson@example.com',
            'password' => 'MyP@ssw0rd123',
            'password_confirmation' => 'MyP@ssw0rd123',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201);

        $user = User::where('email', 'alice.johnson@example.com')->first();
        $team = Team::find($user->team_id);

        // Verify team relationship
        $players = Player::where('team_id', $team->id)->get();
        expect($players->count())->toBeGreaterThan(0);

        foreach ($players as $player) {
            // Verify direct relationship
            expect($player->team_id)->toBe($team->id);

            // Verify model relationship
            expect($player->team)->not->toBeNull();
            expect($player->team->id)->toBe($team->id);

            // Verify reverse relationship
            expect($team->players->contains($player->id))->toBeTrue();
        }
    });

    it('ensures player generation follows exact configuration quantities', function () {
        $userData = [
            'name' => 'Charlie Brown',
            'email' => 'charlie.brown@example.com',
            'password' => 'MyP@ssw0rd123',
            'password_confirmation' => 'MyP@ssw0rd123',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201);

        $user = User::where('email', 'charlie.brown@example.com')->first();
        $team = Team::find($user->team_id);

        $positions = config('soccer.team.positions');

        // Detailed verification for each position
        foreach ($positions as $position => $config) {
            $expectedCount = $config['default_count'];

            // Count players for this position
            $positionPlayers = Player::where('team_id', $team->id)
                ->where('position', $position)
                ->get();

            expect($positionPlayers->count())->toBe($expectedCount);

            // Verify each player in this position has correct attributes
            foreach ($positionPlayers as $player) {
                expect($player->position)->toBe($position);
                expect($player->team_id)->toBe($team->id);
                expect($player->first_name)->not->toBeNull();
                expect($player->last_name)->not->toBeNull();
                expect($player->date_of_birth)->not->toBeNull();
            }
        }
    });
});

describe('Complete Registration Flow Integration', function () {
    it('verifies complete flow: User Registration → Team Creation → Player Generation', function () {
        $userData = [
            'name' => 'Integration Test User',
            'email' => 'integration@example.com',
            'password' => 'MyP@ssw0rd123',
            'password_confirmation' => 'MyP@ssw0rd123',
        ];

        // Step 1: Register user
        $response = $this->postJson('/api/register', $userData);
        $response->assertStatus(201);

        // Step 2: Verify user creation
        $user = User::where('email', 'integration@example.com')->first();
        expect($user)->not->toBeNull();
        expect($user->name)->toBe('Integration Test User');
        expect($user->team_id)->not->toBeNull();

        // Step 3: Verify team creation
        $team = Team::find($user->team_id);
        expect($team)->not->toBeNull();
        expect($team->uuid)->not->toBeNull();
        expect($team->balance)->toBe(number_format(config('soccer.team.initial_balance', 5000000), 2, '.', ''));

        // Step 4: Verify player generation
        $positions = config('soccer.team.positions');
        $expectedTotalPlayers = array_sum(array_column($positions, 'default_count'));

        $actualPlayerCount = Player::where('team_id', $team->id)->count();
        expect($actualPlayerCount)->toBe($expectedTotalPlayers);

        // Step 5: Verify complete relationships
        expect($user->team_id)->toBe($team->id);

        $players = Player::where('team_id', $team->id)->get();
        foreach ($players as $player) {
            expect($player->team_id)->toBe($team->id);
            expect($player->position)->toBeIn(array_keys($positions));
        }

        // Step 6: Verify database integrity
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'integration@example.com',
            'team_id' => $team->id,
        ]);

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'uuid' => $team->uuid,
            'balance' => config('soccer.team.initial_balance', 5000000),
        ]);

        // Verify at least one player exists for each position
        foreach ($positions as $position => $config) {
            $this->assertDatabaseHas('players', [
                'team_id' => $team->id,
                'position' => $position,
            ]);
        }
    });

    it('handles multiple registrations with complete flow verification', function () {
        $users = [
            ['name' => 'User One', 'email' => 'user1@example.com'],
            ['name' => 'User Two', 'email' => 'user2@example.com'],
            ['name' => 'User Three', 'email' => 'user3@example.com'],
        ];

        $createdUsers = [];
        $createdTeams = [];

        // Register multiple users
        foreach ($users as $userData) {
            $registrationData = array_merge($userData, [
                'password' => 'MyP@ssw0rd123',
                'password_confirmation' => 'MyP@ssw0rd123',
            ]);

            $response = $this->postJson('/api/register', $registrationData);
            $response->assertStatus(201);

            $user = User::where('email', $userData['email'])->first();
            $team = Team::find($user->team_id);

            $createdUsers[] = $user;
            $createdTeams[] = $team;
        }

        // Verify all users have unique teams
        $teamIds = array_column($createdTeams, 'id');
        expect($teamIds)->toHaveCount(count(array_unique($teamIds)));

        // Verify each team has correct number of players
        $positions = config('soccer.team.positions');
        $expectedTotalPlayers = array_sum(array_column($positions, 'default_count'));

        foreach ($createdTeams as $team) {
            $playerCount = Player::where('team_id', $team->id)->count();
            expect($playerCount)->toBe($expectedTotalPlayers);

            // Verify each position has correct number of players
            foreach ($positions as $position => $config) {
                $positionCount = Player::where('team_id', $team->id)
                    ->where('position', $position)
                    ->count();
                expect($positionCount)->toBe($config['default_count']);
            }
        }

        // Verify total counts
        $totalUsers = User::count();
        $totalTeams = Team::count();
        $totalPlayers = Player::count();

        expect($totalUsers)->toBe(count($users));
        expect($totalTeams)->toBe(count($users));
        expect($totalPlayers)->toBe(count($users) * $expectedTotalPlayers);
    });
});
