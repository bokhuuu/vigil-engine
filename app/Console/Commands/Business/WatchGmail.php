<?php

declare(strict_types=1);

namespace App\Console\Commands\Business;

use App\Business\Models\Business;
use App\Business\Models\BusinessEmail;
use App\Business\Services\BusinessEmailMatcher;
use App\Services\GmailService;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class WatchGmail extends Command
{
    protected $signature = 'business:watch-gmail';

    protected $description = 'Watch Gmail for business-related emails and route attachments to Drive';

    public function __construct(
        private readonly GmailService $gmail,
        private readonly TelegramService $telegram,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $labelId = $this->gmail->getOrCreateLabel('ბიზნეს მოდული');
        $matcher = new BusinessEmailMatcher;
        $messages = $this->gmail->getUnreadMessages();

        if (empty($messages)) {
            $this->info('No unread messages.');

            return;
        }

        foreach ($messages as $msg) {
            $message = $this->gmail->getMessage($msg->getId());
            $subject = $this->gmail->getMessageSubject($message);
            $from = $this->gmail->getMessageFrom($message);
            $body = $this->gmail->getMessageBody($message);
            $business = $matcher->match($subject, $body);
            $matchedBy = $matcher->matchedBy($subject, $body);

            if (! $business) {
                // Unmatched — alert Zura only
                $this->telegram->sendToMe(
                    "❓ გამოუცნობი ელ-ფოსტა\n\n".
                        "<b>From:</b> {$from}\n".
                        "<b>Subject:</b> {$subject}"
                );

                $this->gmail->markAsRead($msg->getId());

                continue;
            }

            // Apply business label
            $this->gmail->applyLabel($msg->getId(), $labelId);
            $this->gmail->markAsRead($msg->getId());

            // Log in DB
            BusinessEmail::create([
                'business_id' => $business->id,
                'email_from' => $from,
                'email_subject' => $subject,
                'matched_by' => $matchedBy,
                'processed_at' => now(),
            ]);

            // Find latest pending correspondence round
            $latestCorr = $business->correspondence()
                ->whereIn('status', ['pending', 'overdue'])
                ->orderByDesc('round')
                ->first();

            $nextRound = ($business->correspondence()->max('round') ?? 0) + 1;
            $business->correspondence()->create([
                'round' => $nextRound,
                'sent_at' => now(),
                'status' => 'pending',
                'notes' => $subject,
            ]);

            // Get attachments
            $attachments = $this->gmail->getAttachments($message);
            $count = count($attachments);

            $roundFolder = $latestCorr
                ? "მიმოწერა-{$latestCorr->round}/შემომავალი"
                : 'შემომავალი';

            // Telegram alert
            $this->telegram->sendToMe(
                "📥 ახალი ელ-ფოსტა\n\n".
                    "<b>{$business->name}</b>\n".
                    "საქაღალდე: {$roundFolder}\n".
                    "ფაილები: {$count}\n".
                    'From: '.htmlspecialchars($from, ENT_QUOTES)
            );

            $this->info("Matched: {$business->name} — {$count} attachments");
        }
    }
}
