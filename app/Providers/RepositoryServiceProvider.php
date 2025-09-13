<?php

namespace App\Providers;

use App\Repositories\Interfaces\UserRepositoryinterface;
use App\Repositories\UserRepository;
use App\Services\Interfaces\UserServiceInterface;
use App\Services\UserService;
use Carbon\Laravel\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserServiceInterface::class, UserService::class);

        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
    }
}
