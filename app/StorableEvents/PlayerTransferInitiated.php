<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class PlayerTransferInitiated extends ShouldBeStored
{
    public function __construct(
        public int $playerId,
        public string $fromTeamUuid,
        public string $toTeamUuid,
        public float $transferFee
    ) {}
}
