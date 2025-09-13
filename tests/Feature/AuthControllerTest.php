<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

describe('User Registration', function () {
    it('can register a new user with valid data', function () {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'MyP@ssw0rd123',
            'password_confirmation' => 'MyP@ssw0rd123',
        ];

        $response = $this->postJson('/api/register', $userData);

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
            ])
            ->assertJson([
                'message' => 'User registered successfully',
                'data' => [
                    'user' => [
                        'name' => 'John Doe',
                        'email' => 'john.doe@example.com',
                    ],
                ],
            ]);

        // Verify user was created in database
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
        ]);

        // Verify password is hashed
        $user = User::where('email', 'john.doe@example.com')->first();
        expect(Hash::check('MyP@ssw0rd123', $user->password))->toBeTrue();
    });

    it('returns validation error when name is missing', function () {
        $userData = [
            'email' => 'john.doe@example.com',
            'password' => 'MyP@ssw0rd123',
            'password_confirmation' => 'MyP@ssw0rd123',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    it('returns validation error when name is too short', function () {
        $userData = [
            'name' => 'J',
            'email' => 'john.doe@example.com',
            'password' => 'MyP@ssw0rd123',
            'password_confirmation' => 'MyP@ssw0rd123',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    it('returns validation error when name is too long', function () {
        $userData = [
            'name' => str_repeat('A', 256),
            'email' => 'john.doe@example.com',
            'password' => 'MyP@ssw0rd123',
            'password_confirmation' => 'MyP@ssw0rd123',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    it('returns validation error when email is missing', function () {
        $userData = [
            'name' => 'John Doe',
            'password' => 'MyP@ssw0rd123',
            'password_confirmation' => 'MyP@ssw0rd123',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    it('returns validation error when email format is invalid', function () {
        $userData = [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'MyP@ssw0rd123',
            'password_confirmation' => 'MyP@ssw0rd123',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    it('returns validation error when email is too long', function () {
        $userData = [
            'name' => 'John Doe',
            'email' => str_repeat('a', 250).'@example.com',
            'password' => 'MyP@ssw0rd123',
            'password_confirmation' => 'MyP@ssw0rd123',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    it('returns validation error when email already exists', function () {
        // Create existing user
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
    });

    it('returns validation error when password is missing', function () {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password_confirmation' => 'MyP@ssw0rd123',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    it('returns validation error when password is too short', function () {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'Short1!',
            'password_confirmation' => 'Short1!',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    it('returns validation error when password does not contain uppercase letter', function () {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'mypassword123!',
            'password_confirmation' => 'mypassword123!',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    it('returns validation error when password does not contain lowercase letter', function () {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'MYPASSWORD123!',
            'password_confirmation' => 'MYPASSWORD123!',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    it('returns validation error when password does not contain numbers', function () {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'MyPassword!',
            'password_confirmation' => 'MyPassword!',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    it('returns validation error when password does not contain symbols', function () {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'MyPassword123',
            'password_confirmation' => 'MyPassword123',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    it('returns validation error when password confirmation does not match', function () {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'MyP@ssw0rd123',
            'password_confirmation' => 'DifferentPassword123!',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    it('returns validation error when password confirmation is missing', function () {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'MyP@ssw0rd123',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    it('returns validation error when password is compromised', function () {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    it('returns multiple validation errors when multiple fields are invalid', function () {
        $userData = [
            'name' => 'J',
            'email' => 'invalid-email',
            'password' => 'weak',
            'password_confirmation' => 'different',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    });

    it('does not include password in response', function () {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'MyP@ssw0rd123',
            'password_confirmation' => 'MyP@ssw0rd123',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201);

        $responseData = $response->json();
        expect($responseData['data']['user'])->not->toHaveKey('password');
        expect($responseData['data']['user'])->not->toHaveKey('remember_token');
    });

    it('includes created_at timestamp in response', function () {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'MyP@ssw0rd123',
            'password_confirmation' => 'MyP@ssw0rd123',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201);

        $responseData = $response->json();
        expect($responseData['data']['user'])->toHaveKey('created_at');
        expect($responseData['data']['user']['created_at'])->not->toBeNull();
    });

    it('handles registration with edge case names', function () {
        $testCases = [
            ['name' => 'Jo', 'description' => 'minimum length name'],
            ['name' => str_repeat('A', 255), 'description' => 'maximum length name'],
            ['name' => 'José María', 'description' => 'name with special characters'],
            ['name' => '李小明', 'description' => 'name with unicode characters'],
        ];

        foreach ($testCases as $testCase) {
            $userData = [
                'name' => $testCase['name'],
                'email' => 'test'.uniqid().'@example.com',
                'password' => 'MyP@ssw0rd123',
                'password_confirmation' => 'MyP@ssw0rd123',
            ];

            $response = $this->postJson('/api/register', $userData);

            $response->assertStatus(201, "Failed for {$testCase['description']}: {$testCase['name']}");
        }
    });

    it('handles registration with edge case emails', function () {
        $testCases = [
            ['email' => 'a@b.co', 'description' => 'minimum valid email'],
            ['email' => str_repeat('a', 238).'@example.com', 'description' => 'maximum length email'],
            ['email' => 'test+tag@example.com', 'description' => 'email with plus sign'],
            ['email' => 'test.email@example.com', 'description' => 'email with dot'],
        ];

        foreach ($testCases as $testCase) {
            $userData = [
                'name' => 'Test User',
                'email' => $testCase['email'],
                'password' => 'MyP@ssw0rd123',
                'password_confirmation' => 'MyP@ssw0rd123',
            ];

            $response = $this->postJson('/api/register', $userData);

            $response->assertStatus(201, "Failed for {$testCase['description']}: {$testCase['email']}");
        }
    });
});
