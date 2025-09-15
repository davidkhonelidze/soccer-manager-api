<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class PlayerTransferCompleted extends ShouldBeStored
{
    public function __construct(
        public int $playerId,
        public string $newTeamUuid
    ) {}
}
