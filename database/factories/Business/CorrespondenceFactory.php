<?php

declare(strict_types=1);

namespace Database\Factories\Business;

use App\Business\Models\Business;
use App\Business\Models\Correspondence;
use Illuminate\Database\Eloquent\Factories\Factory;

class CorrespondenceFactory extends Factory
{
    protected $model = Correspondence::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'round' => 1,
            'sent_at' => now()->subDays(10),
            'deadline_at' => now()->subDays(2),
            'status' => 'pending',
        ];
    }
}
