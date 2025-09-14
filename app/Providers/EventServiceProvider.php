<?php

namespace App\Providers;

use App\Projectors\TeamProjector;
use Illuminate\Support\ServiceProvider;
use Spatie\EventSourcing\Facades\Projectionist;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Projectors რეგისტრაცია
        Projectionist::addProjector(TeamProjector::class);
    }
}
