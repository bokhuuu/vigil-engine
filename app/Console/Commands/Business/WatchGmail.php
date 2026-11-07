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
        try {
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
                // Skip emails sent by our own team (CC copies of outgoing emails)
                preg_match('/<([^>]+)>/', $from, $matches);
                $fromEmail = strtolower($matches[1] ?? $from);

                $isColliers = str_ends_with($fromEmail, '@colliers.ge');

                $business = $matcher->match($subject, $body);
                $matchedBy = $matcher->matchedBy($subject, $body);

                if (! $business) {
                    if ($isColliers) {
                        $this->gmail->markAsRead($msg->getId());
                    }

                    continue;
                }

                if ($isColliers) {
                    $this->gmail->applyLabel($msg->getId(), $labelId);
                    $this->gmail->markAsRead($msg->getId());

                    BusinessEmail::create([
                        'business_id' => $business->id,
                        'email_from' => $from,
                        'email_subject' => $subject,
                        'matched_by' => $matchedBy,
                        'processed_at' => now(),
                    ]);

                    $nextRound = ($business->correspondence()->max('round') ?? 0) + 1;
                    $business->correspondence()->create([
                        'round' => $nextRound,
                        'sent_at' => now(),
                        'deadline_at' => now()->addDays(10),
                        'status' => 'pending',
                        'notes' => $subject,
                    ]);

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
                    'received_at' => now(),
                    'status' => 'received',
                    'notes' => $subject,
                ]);

                $attachments = $this->gmail->getAttachments($message);
                $count = count($attachments);
                $roundFolder = 'მიმოწერა/'.$openCorr->round;
                $incomingFolder = $roundFolder.'/შემომავალი ფაილები';

                if ($business->drive_folder_url) {
                    $subfolderFolderId = $this->drive->ensureSubfolderPath(
                        $business->drive_folder_url,
                        $incomingFolder,
                    );
                    $subfolderUrl = "https://drive.google.com/drive/folders/{$subfolderFolderId}";

                    $openCorr->update(['drive_folder_url' => $subfolderUrl]);

                    $filename = now()->format('Y-m-d_H-i').'_email.txt';
                    $textContent = "From: {$from}\nSubject: {$subject}\n\n{$body}";

                    $this->drive->uploadFileToSubfolder(
                        $business->drive_folder_url,
                        $incomingFolder,
                        $filename,
                        'text/plain',
                        $textContent,
                    );

                    foreach ($attachments as $attachment) {
                        $this->drive->uploadFileToSubfolder(
                            $business->drive_folder_url,
                            $incomingFolder,
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
        } catch (\InvalidArgumentException $e) {
            Log::error('business:watch-gmail OAuth token invalid: '.$e->getMessage());
            $this->telegram->sendToMe(
                '🔑 <b>business:watch-gmail</b> — OAuth token expired or invalid.'."\n".
                    'Fix: <code>php artisan vigil:drive-auth</code> then <code>php artisan vigil:drive-watch --seed</code>'
            );
        }
    }
}
