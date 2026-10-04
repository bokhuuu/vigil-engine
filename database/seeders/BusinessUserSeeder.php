<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Business\Models\User;
use Illuminate\Database\Seeder;

class BusinessUserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => env('BUSINESS_SEED_NAME', 'Zura'),
            'email' => env('BUSINESS_SEED_EMAIL'),
            'password' => env('BUSINESS_SEED_PASSWORD'),
        ]);
    }
}
