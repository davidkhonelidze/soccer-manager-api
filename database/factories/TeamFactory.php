<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $countries = Country::all()->pluck('id');
        $suffixes = ['FC', 'United', 'City', 'Athletic', 'Rovers', 'Dynamo'];
        return [
            'name' => $this->faker->word() . ' ' . $this->faker->randomElement($suffixes),
            'country_id' => $this->faker->randomElement($countries),
        ];
    }
}
