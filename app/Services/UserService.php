<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryinterface;
use App\Services\Interfaces\TeamServiceInterface;
use App\Services\Interfaces\UserServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService implements UserServiceInterface
{
    public function __construct(private UserRepositoryinterface $repository,
        private TeamServiceInterface $teamService) {}

    public function register(array $data): User
    {
        try {
            return DB::transaction(function () use ($data) {
                $data['password'] = Hash::make($data['password']);

                $team = $this->teamService->createTeam([]);

                $data['team_id'] = $team->id;

                $this->teamService->populateTeamWithPlayers($data['team_id']);

                return $this->repository->create($data);
            });
        } catch (\Exception $e) {
            throw $e;
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

    public function find(int $id): ?User
    {
        return $this->repository->find($id);
    }

    public function getCurrentUserWithTeam(int $userId): ?User
    {
        return $this->repository->findWithTeamData($userId);
    }
}
