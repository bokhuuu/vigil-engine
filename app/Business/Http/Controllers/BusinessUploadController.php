<?php

declare(strict_types=1);

namespace App\Business\Http\Controllers;

use App\Business\Models\Business;
use App\Business\Services\BusinessDriveService;
use App\Services\GoogleDriveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;

class BusinessUploadController extends Controller
{
    public function __construct(
        private readonly GoogleDriveService $drive,
        private readonly BusinessDriveService $businessDrive,
    ) {}

    public function folders(Request $request): JsonResponse
    {
        $request->validate([
            'business_id' => ['required', 'integer', 'exists:business.businesses,id'],
        ]);

        $business = Business::findOrFail($request->integer('business_id'));

        if (! $business->drive_folder_url) {
            return response()->json(['error' => 'ბიზნესს Drive საქაღალდე არ აქვს'], 422);
        }

        $folders = $this->drive->listSubfoldersFromUrl(
            $business->drive_folder_url,
            exclude: ['მიმოწერა'],
        );

        return response()->json([
            'folders' => $folders,
            'business' => [
                'name' => $business->name,
                'code' => $business->registration_code,
            ],
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'business_id' => ['required', 'integer', 'exists:business.businesses,id'],
            'folder' => ['required', 'string', 'max:255'],
            'files' => ['required', 'array', 'min:1', 'max:20'],
            'files.*' => ['required', 'file', 'max:102400'],
        ]);

        $business = Business::findOrFail($request->integer('business_id'));

        if (! $business->drive_folder_url) {
            return response()->json(['error' => 'ბიზნესს Drive საქაღალდე არ აქვს'], 422);
        }

        $subfolderId = $this->drive->findOrCreateSubfolder(
            $business->drive_folder_url,
            $request->string('folder')->toString(),
        );

        $uploaded = [];
        $failed = [];

        foreach ($request->file('files') as $file) {
            $filename = $file->getClientOriginalName();
            $lockKey = 'upload:'.$business->id.':'.md5($filename);

            $lock = Cache::lock($lockKey, 10);

            if (! $lock->get()) {
                $failed[] = $filename;

                continue;
            }

            try {
                $this->businessDrive->uploadFileToDrive(
                    $subfolderId,
                    $filename,
                    file_get_contents($file->getRealPath()),
                    $file->getMimeType() ?? 'application/octet-stream',
                );
                $uploaded[] = $filename;
            } catch (\Throwable $e) {
                $failed[] = $filename;
            } finally {
                $lock->release();
            }
        }

        return response()->json([
            'uploaded' => $uploaded,
            'failed' => $failed,
        ]);
    }
}
