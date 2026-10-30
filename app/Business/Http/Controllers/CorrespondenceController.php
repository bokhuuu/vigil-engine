<?php

declare(strict_types=1);

namespace App\Business\Http\Controllers;

use App\Business\Models\Business;
use App\Business\Models\Correspondence;
use App\Business\Services\BusinessDriveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class CorrespondenceController extends Controller
{
    public function __construct(private readonly BusinessDriveService $driveService) {}

    public function store(Request $request, Business $business): RedirectResponse
    {
        $validated = $request->validate([
            'sent_at' => ['required', 'date'],
            'deadline_at' => ['required', 'date', 'after:sent_at'],
            'notes' => ['nullable', 'string'],
            'recipient_email' => ['nullable', 'string'],
            'cc_emails' => ['nullable', 'string'],
            'subject' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
        ]);

        $ccEmails = null;
        if (! empty($validated['cc_emails'])) {
            $decoded = json_decode($validated['cc_emails'], true);
            $ccEmails = is_array($decoded) ? $decoded : null;
        }

        $round = $business->correspondence()->max('round') + 1;

        $business->correspondence()->create([
            'round' => $round,
            'sent_at' => $validated['sent_at'],
            'deadline_at' => $validated['deadline_at'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
            'recipient_email' => $validated['recipient_email'] ?? null,
            'cc_emails' => $ccEmails,
            'subject' => $validated['subject'] ?? null,
            'body' => $validated['body'] ?? null,
        ]);

        try {
            $this->driveService->createCorrespondenceRoundFolders($business, $round);
        } catch (\Throwable $e) {
            Log::warning("Drive folder creation failed for business [{$business->id}] round [{$round}]: ".$e->getMessage());
        }

        if (! empty($validated['body'])) {
            try {
                $corr = $business->correspondence()->where('round', $round)->first();
                if ($corr?->drive_folder_url) {
                    $folderId = $this->driveService->getFolderIdFromUrl($corr->drive_folder_url);
                    $this->driveService->uploadFileToDrive($folderId, 'წერილი.txt', $validated['body'], 'text/plain');
                    Log::info("CorrespondenceController: saved outgoing body to Drive for business [{$business->id}] round [{$round}]");
                }
            } catch (\Throwable $e) {
                Log::warning("CorrespondenceController: failed to save body to Drive for business [{$business->id}] round [{$round}]: ".$e->getMessage());
            }
        }

        return redirect()->route('business.show', $business)->with('success', 'მიმოწერა-'.$round.' დაემატა');
    }

    public function markReceived(Business $business, Correspondence $correspondence): RedirectResponse
    {
        $correspondence->update([
            'status' => 'received',
            'received_at' => now(),
        ]);

        return redirect()->route('business.show', $business)->with('success', 'მიმოწერა-'.$correspondence->round.' მიღებულად მოინიშნა');
    }

    public function update(Request $request, Business $business, Correspondence $correspondence): RedirectResponse
    {
        $validated = $request->validate([
            'sent_at' => ['required', 'date'],
            'deadline_at' => ['required', 'date'],
            'received_at' => ['nullable', 'date'],
            'status' => ['required', 'in:pending,active,received,overdue'],
            'notes' => ['nullable', 'string'],
        ]);

        $correspondence->update($validated);

        return redirect()->route('business.show', $business)->with('success', 'მიმოწერა-'.$correspondence->round.' განახლდა');
    }

    public function destroy(Business $business, Correspondence $correspondence): RedirectResponse
    {
        $correspondence->delete();

        return redirect()->route('business.show', $business)->with('success', 'მიმოწერა-'.$correspondence->round.' წაიშალა');
    }
}
