<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Business\Models\Business;
use App\Business\Models\Correspondence;
use Illuminate\Database\Seeder;

class BusinessCorrespondenceSeeder extends Seeder
{
    public function run(): void
    {
        $leaderBetoni = Business::where('registration_code', '248384449')->first();
        $samsheneblо = Business::where('registration_code', '123456789')->first();
        $mshenebeli = Business::where('registration_code', '987654321')->first();

        // ლიდერ ბეტონი — 2 rounds
        Correspondence::create([
            'business_id' => $leaderBetoni->id,
            'round' => 1,
            'sent_at' => '2026-09-01',
            'deadline_at' => '2026-09-15',
            'received_at' => '2026-09-10',
            'status' => 'received',
            'notes' => 'პირველი მოთხოვნა გაგზავნილია',
        ]);

        Correspondence::create([
            'business_id' => $leaderBetoni->id,
            'round' => 2,
            'sent_at' => '2026-09-20',
            'deadline_at' => '2026-10-04',
            'received_at' => null,
            'status' => 'pending',
            'notes' => 'მეორე მოთხოვნა — ელოდება პასუხს',
        ]);

        // სამშენებლო კომპანია — 1 round overdue
        Correspondence::create([
            'business_id' => $samsheneblо->id,
            'round' => 1,
            'sent_at' => '2026-09-01',
            'deadline_at' => '2026-09-15',
            'received_at' => null,
            'status' => 'overdue',
            'notes' => 'ვადა გადაცილდა',
        ]);

        // მშენებელი — 1 round received
        Correspondence::create([
            'business_id' => $mshenebeli->id,
            'round' => 1,
            'sent_at' => '2026-08-15',
            'deadline_at' => '2026-08-30',
            'received_at' => '2026-08-25',
            'status' => 'received',
            'notes' => null,
        ]);
    }
}
