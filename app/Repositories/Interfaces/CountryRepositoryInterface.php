<?php

namespace App\Repositories\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CountryRepositoryInterface
{
    public function paginate($perPage = 15): LengthAwarePaginator;
}
