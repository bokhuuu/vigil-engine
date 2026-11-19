<?php

declare(strict_types=1);

namespace App\Business\Http\Controllers;

use App\Business\Models\Business;
use App\Business\Services\BusinessDriveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class BusinessMoveController extends Controller
{
    public function __construct(private readonly BusinessDriveService $driveService) {}

    public function files(Business $business): JsonResponse
    {
        $files = $this->driveService->listIncomingFiles($business);
        $subfolders = config('vigil.business.drive_subfolders');
        $hasIncomingRounds = $business->correspondence()
            ->whereNotNull('received_at')
            ->whereNull('sent_at')
            ->exists();

        return response()->json([
            'files' => $files,
            'subfolders' => $subfolders,
            'has_incoming_rounds' => $hasIncomingRounds,
        ]);
    }

    public function move(Request $request, Business $business): JsonResponse
    {
        $allowedSubfolders = config('vigil.business.drive_subfolders');

        $validated = $request->validate([
            'file_id' => ['required', 'string'],
            'subfolder_name' => ['required', 'string', 'in:'.implode(',', $allowedSubfolders)],
        ]);

        $ownedFileIds = array_column($this->driveService->listIncomingFiles($business), 'id');

        if (! in_array($validated['file_id'], $ownedFileIds, true)) {
            return response()->json(['error' => 'File not found in incoming files'], 403);
        }

        $this->driveService->moveFileToSubfolder(
            $business,
            $validated['file_id'],
            $validated['subfolder_name'],
        );

        return response()->json(['ok' => true]);
    }
}
