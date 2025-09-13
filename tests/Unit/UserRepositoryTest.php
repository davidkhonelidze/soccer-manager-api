<?php

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;

describe('UserRepository', function () {
    it('creates a new user', function () {
        $repository = new UserRepository(new User());

        $userData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => bcrypt('plain_password'), // ✅ bcrypt გამოყენება
        ];

        $user = $repository->create($userData);

        expect($user)->toBeInstanceOf(User::class)
            ->and($user->name)->toBe('John Doe')
            ->and($user->email)->toBe('john.doe@example.com')
            ->and($user->password)->not->toBeNull() // ✅ არა exact match, მხოლოდ არ იყოს null
            ->and($user->id)->not->toBeNull();

        // Password verification - check if bcrypt worked
        expect(Hash::check('plain_password', $user->password))->toBeTrue();

        // Database verification - keep Laravel assertions for now
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
        ]);
    });

    it('creates a user with unicode characters in name', function () {
        $repository = new UserRepository(new User());

        $userData = [
            'name' => 'José María García',
            'email' => 'jose.garcia@example.com',
            'password' => 'hashed_password_here',
        ];

        $user = $repository->create($userData);

        expect($user)->toBeInstanceOf(User::class)
            ->and($user->name)->toBe('José María García')
            ->and($user->email)->toBe('jose.garcia@example.com');

        $this->assertDatabaseHas('users', [
            'name' => 'José María García',
            'email' => 'jose.garcia@example.com',
        ]);
    });

    it('creates a user with maximum length name', function () {
        $repository = new UserRepository(new User());

        $maxName = str_repeat('A', 255);
        $userData = [
            'name' => $maxName,
            'email' => 'test@example.com',
            'password' => 'hashed_password_here',
        ];

        $user = $repository->create($userData);

        expect($user)->toBeInstanceOf(User::class)
            ->and($user->name)->toBe($maxName)
            ->and($user->email)->toBe('test@example.com');

        $this->assertDatabaseHas('users', [
            'name' => $maxName,
            'email' => 'test@example.com',
        ]);
    });

    it('creates a user with long email address', function () {
        $repository = new UserRepository(new User());

        $longEmail = str_repeat('a', 238).'@example.com';
        $userData = [
            'name' => 'Test User',
            'email' => $longEmail,
            'password' => 'hashed_password_here',
        ];

        $user = $repository->create($userData);

        expect($user)->toBeInstanceOf(User::class)
            ->and($user->name)->toBe('Test User')
            ->and($user->email)->toBe($longEmail);

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => $longEmail,
        ]);
    });

    it('finds user by email', function () {
        $repository = new UserRepository(new User());

        // Create a user first
        $userData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'hashed_password_here',
        ];
        $createdUser = $repository->create($userData);

        // Find the user by email
        $foundUser = $repository->findByEmail('john.doe@example.com');

        expect($foundUser)->toBeInstanceOf(User::class)
            ->and($foundUser->id)->toBe($createdUser->id)
            ->and($foundUser->name)->toBe('John Doe')
            ->and($foundUser->email)->toBe('john.doe@example.com');
    });

    it('returns null when user is not found by email', function () {
        $repository = new UserRepository(new User());

        $foundUser = $repository->findByEmail('nonexistent@example.com');

        expect($foundUser)->toBeNull();
    });

    it('handles multiple users with different emails', function () {
        $repository = new UserRepository(new User());

        $users = [
            [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'password' => 'hashed_password_1',
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane.smith@example.com',
                'password' => 'hashed_password_2',
            ],
            [
                'name' => 'Bob Johnson',
                'email' => 'bob.johnson@example.com',
                'password' => 'hashed_password_3',
            ],
        ];

        $createdUsers = [];
        foreach ($users as $userData) {
            $createdUsers[] = $repository->create($userData);
        }

        // Find each user by email
        foreach ($createdUsers as $index => $createdUser) {
            $foundUser = $repository->findByEmail($users[$index]['email']);

            expect($foundUser)->toBeInstanceOf(User::class)
                ->and($foundUser->id)->toBe($createdUser->id)
                ->and($foundUser->name)->toBe($users[$index]['name'])
                ->and($foundUser->email)->toBe($users[$index]['email']);
        }
    });

    it('creates user with all fillable attributes', function () {
        $repository = new UserRepository(new User());

        $userData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'hashed_password_here',
        ];

        $user = $repository->create($userData);

        expect($user->name)->toBe('John Doe')
            ->and($user->email)->toBe('john.doe@example.com')
            ->and($user->created_at)->not->toBeNull()
            ->and($user->updated_at)->not->toBeNull();
    });

    it('assigns unique IDs to different users', function () {
        $repository = new UserRepository(new User());

        $user1 = $repository->create([
            'name' => 'User One',
            'email' => 'user1@example.com',
            'password' => 'password1',
        ]);

        $user2 = $repository->create([
            'name' => 'User Two',
            'email' => 'user2@example.com',
            'password' => 'password2',
        ]);

        expect($user1->id)->not->toBe($user2->id)
            ->and($user1->id)->toBeInt()
            ->and($user2->id)->toBeInt();
    });
});
