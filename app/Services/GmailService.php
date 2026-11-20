<?php

declare(strict_types=1);

namespace App\Services;

use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Label;
use Google\Service\Gmail\Message;
use Google\Service\Gmail\ModifyMessageRequest;
use Illuminate\Support\Facades\Log;

class GmailService
{
    private Gmail $gmail;

    private string $userId = 'me';

    public function __construct(private readonly Client $client) {}

    private function connect(): void
    {
        if (isset($this->gmail)) {
            return;
        }

        $tokenFile = config('services.google_drive.oauth_token');
        $this->client->setAuthConfig(config('services.google_drive.oauth_client'));
        $this->client->addScope(Gmail::GMAIL_MODIFY);
        $this->client->setAccessType('offline');

        $token = json_decode(file_get_contents($tokenFile), true);
        $this->client->setAccessToken($token);

        if ($this->client->isAccessTokenExpired()) {
            $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
            file_put_contents($tokenFile, json_encode($this->client->getAccessToken(), JSON_PRETTY_PRINT));
        }

        $this->gmail = new Gmail($this->client);
    }

    public function getUnreadMessages(string $query = ''): array
    {
        $this->connect();

        $results = $this->gmail->users_messages->listUsersMessages($this->userId, [
            'q' => 'is:unread in:anywhere -label:ბიზნეს-მოდული '.$query,
            'maxResults' => 50,
        ]);

        return $results->getMessages() ?? [];
    }

    public function getMessage(string $messageId): Message
    {
        $this->connect();

        return $this->gmail->users_messages->get($this->userId, $messageId, [
            'format' => 'full',
        ]);
    }

    public function getMessageSubject(Message $message): string
    {
        foreach ($message->getPayload()->getHeaders() as $header) {
            if ($header->getName() === 'Subject') {
                return $header->getValue();
            }
        }

        return '';
    }

    public function getMessageFrom(Message $message): string
    {
        foreach ($message->getPayload()->getHeaders() as $header) {
            if ($header->getName() === 'From') {
                return $header->getValue();
            }
        }

        return '';
    }

    public function getMessageCc(Message $message): string
    {
        foreach ($message->getPayload()->getHeaders() as $header) {
            if ($header->getName() === 'Cc') {
                return $header->getValue();
            }
        }

        return '';
    }

    public function getMessageTo(Message $message): string
    {
        foreach ($message->getPayload()->getHeaders() as $header) {
            if ($header->getName() === 'To') {
                return $header->getValue();
            }
        }

        return '';
    }

    public function getMessageBody(Message $message): string
    {
        $payload = $message->getPayload();

        $text = $this->extractTextPlain($payload->getParts() ?? []);
        if ($text !== '') {
            return $text;
        }

        if ($payload->getBody()->getData()) {
            return base64_decode(strtr($payload->getBody()->getData(), '-_', '+/'));
        }

        return '';
    }

    private function extractTextPlain(array $parts): string
    {
        foreach ($parts as $part) {
            if ($part->getMimeType() === 'text/plain' && $part->getBody()->getData()) {
                return base64_decode(strtr($part->getBody()->getData(), '-_', '+/'));
            }
            if ($part->getParts()) {
                $nested = $this->extractTextPlain($part->getParts());
                if ($nested !== '') {
                    return $nested;
                }
            }
        }

        return '';
    }

    public function getAttachments(Message $message): array
    {
        $this->connect();

        $attachments = [];
        $this->collectAttachments($message->getPayload()->getParts() ?? [], $message->getId(), $attachments);

        return $attachments;
    }

    private function collectAttachments(array $parts, string $messageId, array &$attachments): void
    {
        foreach ($parts as $part) {
            if ($part->getParts()) {
                $this->collectAttachments($part->getParts(), $messageId, $attachments);
            }

            if ($part->getFilename() && $part->getBody()->getAttachmentId()) {
                $attachmentData = $this->gmail->users_messages_attachments->get(
                    $this->userId,
                    $messageId,
                    $part->getBody()->getAttachmentId()
                );

                $attachments[] = [
                    'filename' => $part->getFilename(),
                    'mimeType' => $part->getMimeType(),
                    'data' => base64_decode(strtr($attachmentData->getData(), '-_', '+/')),
                ];
            }
        }
    }

    public function applyLabel(string $messageId, string $labelId): void
    {
        $this->connect();

        $request = new ModifyMessageRequest;
        $request->setAddLabelIds([$labelId]);
        $this->gmail->users_messages->modify($this->userId, $messageId, $request);
    }

    public function markAsRead(string $messageId): void
    {
        $this->connect();

        $request = new ModifyMessageRequest;
        $request->setRemoveLabelIds(['UNREAD']);
        $this->gmail->users_messages->modify($this->userId, $messageId, $request);
    }

    public function getOrCreateLabel(string $name): string
    {
        $this->connect();

        $labels = $this->gmail->users_labels->listUsersLabels($this->userId);

        foreach ($labels->getLabels() as $label) {
            if ($label->getName() === $name) {
                return $label->getId();
            }
        }

        $label = new Label;
        $label->setName($name);
        $created = $this->gmail->users_labels->create($this->userId, $label);

        Log::info("GmailService: created label [{$name}]");

        return $created->getId();
    }
}
