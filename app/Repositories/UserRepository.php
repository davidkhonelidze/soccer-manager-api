<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryinterface;

class UserRepository implements UserRepositoryinterface
{
    public function __construct(
        protected User $model
    ) {}

    public function create(array $data): User
    {
        return $this->model->create($data);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    public function find(int $id): ?User
    {
        return $this->model->find($id);
    }
}
