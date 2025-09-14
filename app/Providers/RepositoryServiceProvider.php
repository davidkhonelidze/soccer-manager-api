<?php

namespace App\Providers;

use App\Repositories\CountryRepository;
use App\Repositories\Interfaces\CountryRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryinterface;
use App\Repositories\UserRepository;
use App\Services\CountryService;
use App\Services\Interfaces\CountryServiceInterface;
use App\Services\Interfaces\UserServiceInterface;
use App\Services\UserService;
use Carbon\Laravel\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(CountryServiceInterface::class, CountryService::class);

        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(CountryRepositoryInterface::class, CountryRepository::class);
    }
}
