<?php

namespace App\Services;

use App\Repositories\Interfaces\CountryRepositoryInterface;
use App\Services\Interfaces\CountryServiceInterface;

class CountryService implements CountryServiceInterface
{
    public function __construct(protected CountryRepositoryInterface $repository) {}

    public function getPaginatedCountries($perPage = 15)
    {
        return $this->repository->paginate($perPage);
    }
}
