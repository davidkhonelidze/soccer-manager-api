<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class FundsTransferred extends ShouldBeStored
{
    public function __construct(
        public string $fromTeamUuid,
        public string $toTeamUuid,
        public float $amount,
        public string $reason = 'Player transfer'
    ) {}
}
