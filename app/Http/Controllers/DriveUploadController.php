<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ActRequirements;
use App\Services\AirtableService;
use App\Services\GoogleDriveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DriveUploadController
{
    public function __construct(
        private AirtableService $airtable,
        private ActRequirements $requirements,
        private GoogleDriveService $drive,
    ) {}

    private function cachedActs(): array
    {
        $snapshot = Cache::get(DashboardController::SNAPSHOT_KEY);

        if ($snapshot === null) {
            $snapshot = ['acts' => $this->airtable->getAllActs()];
        }

        return $snapshot['acts'];
    }

    public function folders(Request $request): JsonResponse
    {
        $recordId = $request->query('act');
        $act = collect($this->cachedActs())->firstWhere('id', $recordId);

        if (! $act) {
            return response()->json(['error' => 'ACT not found'], 404);
        }

        $folders = $this->requirements->requiredSubfolders($act['fields']);

        return response()->json(['folders' => $folders]);
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'act_id' => ['required', 'string'],
            'folder' => ['required', 'string'],
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file', 'max:102400'],
            'paths' => ['sometimes', 'array'],
            'paths.*' => ['sometimes', 'string', 'max:500'],
        ]);

        $act = collect($this->cachedActs())->firstWhere('id', $request->act_id);

        if (! $act) {
            return response()->json(['error' => 'ACT not found'], 404);
        }

        $folderUrl = $act['fields']['💼 საქაღალდე'] ?? null;

        if (! $folderUrl) {
            return response()->json(['error' => 'ACT has no Drive folder'], 422);
        }

        $uploaded = [];
        $failed = [];
        $paths = $request->input('paths', []);
        $folderIdCache = [];

        foreach ($request->file('files') as $index => $file) {
            try {
                $relativePath = $paths[$index] ?? $file->getClientOriginalName();
                $dirname = ltrim(dirname($relativePath), '.');

                if ($dirname !== '') {
                    $subfolderPath = $request->folder.'/'.$dirname;
                    $folderId = $folderIdCache[$subfolderPath]
                        ??= $this->drive->ensureSubfolderPath($folderUrl, $subfolderPath);
                } else {
                    $folderId = $folderIdCache[$request->folder]
                        ??= $this->drive->findOrCreateSubfolder($folderUrl, $request->folder);
                }

                $this->drive->uploadFile(
                    $folderId,
                    basename($relativePath),
                    file_get_contents($file->getRealPath()),
                    $file->getMimeType() ?? 'application/octet-stream',
                );
                $uploaded[] = $file->getClientOriginalName();
            } catch (\Throwable $e) {
                $failed[] = $file->getClientOriginalName();
            }
        }

        return response()->json([
            'uploaded' => $uploaded,
            'failed' => $failed,
        ]);
    }
}
