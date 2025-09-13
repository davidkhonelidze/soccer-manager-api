<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryinterface;
use App\Services\Interfaces\UserServiceInterface;
use Illuminate\Support\Facades\Hash;

class UserService implements UserServiceInterface
{

    public function __construct(private UserRepositoryinterface $repository)
    {

    }

    public function register(array $data): User
    {
        try {
            $data['password'] = Hash::make($data['password']);

            $user = $this->repository->create($data);

            return $user;
        } catch (\Exception $e) {

        }
    }
}
