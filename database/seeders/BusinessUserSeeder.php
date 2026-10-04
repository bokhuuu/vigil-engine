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
            'name'     => 'Zura',
            'email'    => 'zura.colliers.bypass@gmail.com',
            'password' => 'vigil2026',
        ]);
    }
}
