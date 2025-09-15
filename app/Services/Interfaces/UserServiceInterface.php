<?php

namespace App\Services\Interfaces;

use App\Models\User;

interface UserServiceInterface
{
    public function register(array $data): User;

    public function login(string $email, string $password): array;

    public function find(int $id): ?User;
}
