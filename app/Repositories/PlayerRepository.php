<?php

namespace App\Repositories;

use App\Models\Player;
use App\Repositories\Interfaces\PlayerRepositoryInterface;

class PlayerRepository implements PlayerRepositoryInterface
{
    public function __construct(
        protected Player $model
    ) {}

}
