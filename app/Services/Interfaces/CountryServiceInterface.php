<?php

namespace App\Services\Interfaces;

interface CountryServiceInterface
{
    public function getPaginatedCountries($perPage = 15);
}
