<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = [
            [
                'name' => ['ka' => 'საქართველო', 'en' => 'Georgia'],
                'code' => 'GE'
            ],
            [
                'name' => ['ka' => 'იაპონია', 'en' => 'Japan'],
                'code' => 'JP'
            ],
            [
                'name' => ['ka' => 'გერმანია', 'en' => 'Germany'],
                'code' => 'DE'
            ],
        ];

        foreach ($countries as $data) {
            Country::create($data);
        }
    }
}
