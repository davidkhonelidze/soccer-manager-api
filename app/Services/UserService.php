<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryinterface;
use App\Services\Interfaces\UserServiceInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService implements UserServiceInterface
{
    public function __construct(private UserRepositoryinterface $repository) {}

    public function register(array $data): User
    {
        try {
            $data['password'] = Hash::make($data['password']);

            $user = $this->repository->create($data);

            return $user;
        } catch (\Exception $e) {

        }
    }

    public function login(string $email, string $password): array
    {
        $user = $this->repository->findByEmail($email);

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
