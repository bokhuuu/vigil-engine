<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Business\Models\Business;
use Illuminate\Database\Seeder;

class BusinessSeeder extends Seeder
{
    public function run(): void
    {
        $businesses = [
            [
                'name' => 'შპს ლიდერ ბეტონი',
                'registration_code' => '248384449',
                'lot_number' => 2,
                'type' => 'მეპატრონე',
                'status' => 'active',
                'owner_name' => 'გიორგი მამალაძე',
                'owner_phone' => '555123456',
                'owner_email' => 'g.mamaladze@example.com',
                'notes' => 'პირველადი კონტაქტი დამყარდა',
            ],
            [
                'name' => 'შპს სამშენებლო კომპანია',
                'registration_code' => '123456789',
                'lot_number' => 3,
                'type' => 'მოიჯარე',
                'status' => 'pending',
                'owner_name' => 'ნინო ბერიძე',
                'owner_phone' => '555654321',
                'owner_email' => null,
                'notes' => null,
            ],
            [
                'name' => 'შპს მშენებელი',
                'registration_code' => '987654321',
                'lot_number' => 4,
                'type' => 'მეიჯარე',
                'status' => 'complete',
                'owner_name' => 'დავით კვარაცხელია',
                'owner_phone' => '555789456',
                'owner_email' => 'd.kvaratskelia@example.com',
                'accountant_name' => 'მარიამ გელაშვილი',
                'accountant_phone' => '555321654',
                'notes' => 'ყველა დოკუმენტი მიღებულია',
            ],
            [
                'name' => 'შპს გზამშენი',
                'registration_code' => '456789123',
                'lot_number' => 2,
                'type' => 'მეპატრონე',
                'status' => 'active',
                'owner_name' => 'ლევან ჯავახიშვილი',
                'owner_phone' => '555456789',
                'owner_email' => null,
                'notes' => 'პასუხს არ გვცემს',
            ],
            [
                'name' => 'შპს ბეტონპლუსი',
                'registration_code' => '789123456',
                'lot_number' => 5,
                'type' => 'სხვა',
                'status' => 'pending',
                'owner_name' => null,
                'owner_phone' => null,
                'owner_email' => null,
                'notes' => null,
            ],
        ];

        foreach ($businesses as $business) {
            Business::create($business);
        }
    }
}
