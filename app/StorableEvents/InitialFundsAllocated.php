<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class InitialFundsAllocated extends ShouldBeStored
{
    public function __construct(public float $amount,
                                public string $reason = 'Initial team funding')
    {

    }
}
