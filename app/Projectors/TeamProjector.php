<?php

namespace App\Projectors;

use App\Models\Team;
use App\StorableEvents\InitialFundsAllocated;
use App\StorableEvents\TeamCreated;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;

class TeamProjector extends Projector
{
    public function onTeamCreated(TeamCreated $event): void
    {
        Team::factory()->create([
            'uuid' => $event->aggregateRootUuid(),
            'balance' => 0,
        ]);
    }

    public function onInitialFundsAllocated(InitialFundsAllocated $event): void
    {
        Team::where('uuid', $event->aggregateRootUuid())
            ->increment('balance', $event->amount);
    }
}
