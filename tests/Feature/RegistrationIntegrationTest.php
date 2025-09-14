<?php

use App\Models\User;
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
