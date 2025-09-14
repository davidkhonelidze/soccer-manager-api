<?php

namespace App\Aggregates;

use App\StorableEvents\InitialFundsAllocated;
use App\StorableEvents\TeamCreated;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;

class TeamAggregate extends AggregateRoot
{
    private float $balance = 0;

    public function createTeam(): self
    {
        $this->recordThat(new TeamCreated());
        return $this;
    }

    public function allocateInitialFunds(float $amount): self
    {
        $this->recordThat(new InitialFundsAllocated($amount));
        return $this;
    }

    public function applyTeamCreated(TeamCreated $event): void
    {
    }

    public function applyInitialFundsAllocated(InitialFundsAllocated $event): void
    {
        $this->balance += $event->amount;
    }
}
