<?php

declare(strict_types=1);

namespace App\Console\Commands\Business;

use App\Business\Models\Correspondence;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class CheckCorrespondenceOverdue extends Command
{
    protected $signature = 'business:check-overdue';

    protected $description = 'Mark overdue correspondence and send Telegram alerts';

    public function __construct(private readonly TelegramService $telegram)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $overdue = Correspondence::on('business')
            ->where('status', 'pending')
            ->where('deadline_at', '<', now()->toDateString())
            ->with('business')
            ->get();

        if ($overdue->isEmpty()) {
            $this->info('No overdue correspondence found.');

            return;
        }

        foreach ($overdue as $corr) {
            $corr->update(['status' => 'overdue']);

            $message = "🔴 ვადაგადაცილებული მიმოწერა\n\n"
                ."<b>{$corr->business->name}</b>\n"
                ."მიმოწერა-{$corr->round}\n"
                ."ვადა: {$corr->deadline_at->format('d.m.Y')}\n"
                ."კოდი: {$corr->business->registration_code}";

            $this->telegram->sendToMe($message);
        }

        $this->info("Marked {$overdue->count()} correspondence(s) as overdue.");
    }
}
