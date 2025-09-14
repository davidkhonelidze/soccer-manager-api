<?php

namespace App\Repositories;

use App\Models\Country;
use App\Repositories\Interfaces\CountryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CountryRepository implements CountryRepositoryInterface
{
    public function __construct(
        protected Country $model
    ) {}

    public function paginate($perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }
}
