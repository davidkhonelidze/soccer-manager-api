<?php

namespace App\Providers;

use App\Repositories\CountryRepository;
use App\Repositories\Interfaces\CountryRepositoryInterface;
use App\Repositories\Interfaces\PlayerRepositoryInterface;
use App\Repositories\Interfaces\TeamRepositoryInterface;
use App\Repositories\Interfaces\TransferListingRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryinterface;
use App\Repositories\PlayerRepository;
use App\Repositories\TeamRepository;
use App\Repositories\TransferListingRepository;
use App\Repositories\UserRepository;
use App\Services\CountryService;
use App\Services\Interfaces\CountryServiceInterface;
use App\Services\Interfaces\PlayerAuthorizationServiceInterface;
use App\Services\Interfaces\PlayerServiceInterface;
use App\Services\Interfaces\TeamServiceInterface;
use App\Services\Interfaces\TransferListingServiceInterface;
use App\Services\Interfaces\TransferServiceInterface;
use App\Services\Interfaces\UserServiceInterface;
use App\Services\PlayerAuthorizationService;
use App\Services\PlayerService;
use App\Services\TeamService;
use App\Services\TransferListingService;
use App\Services\TransferService;
use App\Services\UserService;
use Carbon\Laravel\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(CountryServiceInterface::class, CountryService::class);
        $this->app->bind(TeamServiceInterface::class, TeamService::class);
        $this->app->bind(PlayerServiceInterface::class, PlayerService::class);
        $this->app->bind(PlayerAuthorizationServiceInterface::class, PlayerAuthorizationService::class);
        $this->app->bind(\App\Services\Interfaces\TeamAuthorizationServiceInterface::class, \App\Services\TeamAuthorizationService::class);
        $this->app->bind(TransferListingServiceInterface::class, TransferListingService::class);
        $this->app->bind(TransferServiceInterface::class, TransferService::class);

        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(CountryRepositoryInterface::class, CountryRepository::class);
        $this->app->bind(TeamRepositoryInterface::class, TeamRepository::class);
        $this->app->bind(PlayerRepositoryInterface::class, PlayerRepository::class);
        $this->app->bind(TransferListingRepositoryInterface::class, TransferListingRepository::class);
    }
}
