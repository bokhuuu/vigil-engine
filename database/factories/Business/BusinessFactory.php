<?php

declare(strict_types=1);

namespace Database\Factories\Business;

use App\Business\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;

class BusinessFactory extends Factory
{
    protected $model = Business::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'registration_code' => fake()->unique()->numerify('#########'),
            'lot_number' => fake()->numberBetween(2, 5),
            'type' => 'მეპატრონე',
            'status' => 'active',
        ];
    }
}
