<?php

declare(strict_types=1);

namespace App\Console\Commands\Business;

use App\Business\Models\BusinessEmail;
use App\Business\Services\BusinessEmailMatcher;
use App\Services\GmailService;
use App\Services\GoogleDriveService;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class WatchGmail extends Command
{
    protected $signature = 'business:watch-gmail';

    protected $description = 'Watch Gmail for business-related emails and route attachments to Drive';

    public function __construct(
        private readonly GmailService $gmail,
        private readonly TelegramService $telegram,
        private readonly GoogleDriveService $drive,
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

            $nextRound = ($business->correspondence()->max('round') ?? 0) + 1;
            $openCorr = $business->correspondence()->create([
                'round' => $nextRound,
                'sent_at' => now(),
                'status' => 'pending',
                'notes' => $subject,
            ]);

            $attachments = $this->gmail->getAttachments($message);
            $count = count($attachments);

            $roundFolder = "მიმოწერა/{$openCorr->round}/შემომავალი";

            if ($business->drive_folder_url) {
                $filename = now()->format('Y-m-d_H-i').'_email.txt';
                $textContent = "From: {$from}\nSubject: {$subject}\n\n{$body}";

                $this->drive->uploadFileToSubfolder(
                    $business->drive_folder_url,
                    $roundFolder,
                    $filename,
                    'text/plain',
                    $textContent,
                );

                foreach ($attachments as $attachment) {
                    $this->drive->uploadFileToSubfolder(
                        $business->drive_folder_url,
                        $roundFolder,
                        $attachment['filename'],
                        $attachment['mimeType'],
                        $attachment['data'],
                    );
                }
            }

            // Telegram alert
            try {
                $this->telegram->sendToMe(
                    "📥 ახალი ელ-ფოსტა\n\n".
                        "<b>{$business->name}</b>\n".
                        "საქაღალდე: {$roundFolder}\n".
                        'ფაილები: '.($count + 1)."\n".
                        'From: '.str_replace(['<', '>'], ['', ''], $from)
                );
            } catch (\Throwable $e) {
                Log::error('WatchGmail: Telegram failed', ['error' => $e->getMessage()]);
            }
        }
    }
}
