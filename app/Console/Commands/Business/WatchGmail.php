<?php

declare(strict_types=1);

namespace App\Console\Commands\Business;

use App\Business\Models\BusinessEmail;
use App\Business\Services\BusinessEmailMatcher;
use App\Services\GmailService;
use App\Services\GoogleDriveService;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
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
        if (! config('vigil.telegram.notifications_enabled')) {
            $this->info('Notifications disabled — skipping.');

            return;
        }

        try {
            $labelId = $this->gmail->getOrCreateLabel('ბიზნეს მოდული');
            $matcher = new BusinessEmailMatcher;
            $messages = $this->gmail->getUnreadMessages();

            if (empty($messages)) {
                $this->info('No unread messages.');
                Cache::put('business_gmail_last_run', now(), 300);

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

                $attachments = $this->gmail->getAttachments($message);
                if ($isColliers && ! preg_match('/^fwd?\s*:/iu', $subject)) {
                    $hasQuotedOriginal = (bool) preg_match('/^From:\s*.+@(?!colliers\.ge)/im', $body);

                    if (! $hasQuotedOriginal) {
                        $this->gmail->applyLabel($msg->getId(), $labelId);
                        $this->gmail->markAsRead($msg->getId());

                        BusinessEmail::create([
                            'business_id' => $business->id,
                            'gmail_message_id' => $msg->getId(),
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
                }

                // Skip already-processed messages
                if (BusinessEmail::where('gmail_message_id', $msg->getId())->exists()) {
                    continue;
                }

                // Apply business label
                $this->gmail->applyLabel($msg->getId(), $labelId);
                $this->gmail->markAsRead($msg->getId());

                // Log in DB
                BusinessEmail::create([
                    'business_id' => $business->id,
                    'gmail_message_id' => $msg->getId(),
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

                $driveFileIds = $this->gmail->getDriveLinksFromBody($body);
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
                        $extracted = $this->extractArchive($attachment);

                        if ($extracted !== null) {
                            foreach ($extracted as $file) {
                                $this->drive->uploadFileToSubfolder(
                                    $business->drive_folder_url,
                                    $incomingFolder,
                                    $file['filename'],
                                    $file['mimeType'],
                                    $file['data'],
                                );
                            }
                        } else {
                            $this->drive->uploadFileToSubfolder(
                                $business->drive_folder_url,
                                $incomingFolder,
                                $attachment['filename'],
                                $attachment['mimeType'],
                                $attachment['data'],
                            );
                        }
                    }

                    foreach ($driveFileIds as $fileId) {
                        try {
                            $userDrive = $this->drive->getUserDrive();
                            $meta = $userDrive->files->get($fileId, ['fields' => 'name,mimeType']);
                            $content = $userDrive->files->get($fileId, ['alt' => 'media']);
                            $this->drive->uploadFileToSubfolder(
                                $business->drive_folder_url,
                                $incomingFolder,
                                $meta->getName(),
                                $meta->getMimeType(),
                                $content->getBody()->getContents(),
                            );
                        } catch (\Throwable $e) {
                            Log::warning("WatchGmail: failed to download Drive file {$fileId}", [
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }

                // Telegram alert
                try {
                    $this->telegram->sendToMe(
                        "📥 ახალი ელ-ფოსტა\n\n".
                            '<b>'.e($business->name).'</b>'."\n".
                            'საქაღალდე: '.e($roundFolder)."\n".
                            'ფაილები: '.($count + count($driveFileIds) + 1)."\n".
                            'From: '.str_replace(['<', '>'], ['', ''], $from)
                    );
                } catch (\Throwable $e) {
                    Log::error('WatchGmail: Telegram failed', ['error' => $e->getMessage()]);
                }
            }
            Cache::put('business_gmail_last_run', now(), 300);
        } catch (\InvalidArgumentException $e) {
            Log::error('business:watch-gmail OAuth token invalid: '.$e->getMessage());
            $this->telegram->sendToMe(
                '🔑 <b>business:watch-gmail</b> — OAuth token expired or invalid.'."\n".
                    'Fix: <code>php artisan vigil:drive-auth</code> then <code>php artisan vigil:drive-watch --seed</code>'
            );
        }
    }

    private function extractArchive(array $attachment): ?array
    {
        $filename = $attachment['filename'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (! in_array($ext, ['zip', 'rar'], true)) {
            return null;
        }

        $tmpDir = sys_get_temp_dir().'/vigil_extract_'.uniqid();
        mkdir($tmpDir, 0755, true);
        $archivePath = $tmpDir.'/'.$filename;

        try {
            file_put_contents($archivePath, $attachment['data']);

            $files = [];

            if ($ext === 'zip') {
                $zip = new \ZipArchive;
                if ($zip->open($archivePath) !== true) {
                    return null;
                }
                $zip->extractTo($tmpDir.'/out');
                $zip->close();
            } elseif ($ext === 'rar') {
                $outDir = $tmpDir.'/out';
                mkdir($outDir, 0755, true);
                exec('unrar e -y '.escapeshellarg($archivePath).' '.escapeshellarg($outDir).' 2>/dev/null');
            }

            $outDir = $tmpDir.'/out';
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($outDir));

            foreach ($iterator as $file) {
                if (! $file->isFile()) {
                    continue;
                }
                $mime = mime_content_type($file->getPathname()) ?: 'application/octet-stream';
                $files[] = [
                    'filename' => $file->getFilename(),
                    'mimeType' => $mime,
                    'data' => file_get_contents($file->getPathname()),
                ];
            }

            return empty($files) ? null : $files;
        } finally {
            exec('rm -rf '.escapeshellarg($tmpDir));
        }
    }
}
