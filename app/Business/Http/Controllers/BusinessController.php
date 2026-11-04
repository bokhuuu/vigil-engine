<?php

declare(strict_types=1);

namespace App\Business\Http\Controllers;

use App\Business\Models\Business;
use App\Business\Services\BusinessDriveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BusinessController extends Controller
{
    public function __construct(private readonly BusinessDriveService $driveService) {}

    public function createDriveFolder(Business $business): RedirectResponse
    {
        $this->driveService->createBusinessFolder($business);

        return redirect()->route('business.show', $business)->with('success', 'Drive საქაღალდე შეიქმნა');
    }

    public function show(Business $business): View
    {
        $business->load('correspondence', 'driveStates');

        return view('business.show', compact('business'));
    }

    public function update(Request $request, Business $business): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'registration_code' => ['required', 'string', 'max:20', Rule::unique('business.businesses', 'registration_code')->ignore($business->id)],
            'act_number' => ['nullable', 'string', 'max:20'],
            'lot_number' => ['required', 'integer'],
            'cadastral_code' => ['nullable', 'string', 'max:150'],
            'type' => ['required', 'array', 'min:1'],
            'type.*' => ['required', 'in:მეპატრონე,მოიჯარე,მეიჯარე,სხვა'],
            'status' => ['required', 'in:active,pending,complete'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'owner_phone' => ['nullable', 'string', 'max:50'],
            'owner_email' => ['nullable', 'string', 'max:255'],
            'accountant_name' => ['nullable', 'string', 'max:255'],
            'accountant_phone' => ['nullable', 'string', 'max:50'],
            'accountant_email' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'drive_folder_url' => ['nullable', 'url'],
        ]);

        $oldName = $business->name;
        $oldCode = $business->registration_code;

        $business->update($validated);

        if ($business->drive_folder_url && (
            $validated['name'] !== $oldName ||
            $validated['registration_code'] !== $oldCode
        )) {
            try {
                $folderId = $this->driveService->getFolderIdFromUrl($business->drive_folder_url);
                $newFolderName = "{$business->name} ({$business->registration_code})";
                $this->driveService->renameBusinessFolder($folderId, $newFolderName);
            } catch (\Throwable $e) {
                Log::warning("Drive rename failed for business [{$business->id}]: ".$e->getMessage());
            }
        }

        return redirect()->route('business.show', $business)->with('success', 'ბიზნესი განახლდა');
    }

    public function destroy(Business $business): RedirectResponse
    {
        $business->update(['drive_folder_url' => null]);
        $business->delete();

        return redirect()->route('business.dashboard')->with('success', 'ბიზნესი წაიშალა');
    }
}
