<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserFca;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserFca>
 */
class UserFcaFactory extends Factory
{
    protected $model = UserFca::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'organization_name' => fake()->company(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'parking_latitude' => fake()->latitude(4.5, 21.5),
            'parking_longitude' => fake()->longitude(116.0, 127.0),
            'province' => fake()->state(),
            'city_town' => fake()->city(),
            'barangay' => 'Barangay '.fake()->streetName(),
            'date_received' => fake()->dateTimeBetween('-1 year')->format('Y-m-d'),
        ];
    }
}
