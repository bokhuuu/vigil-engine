<?php

declare(strict_types=1);

namespace App\Business\Http\Controllers;

use App\Business\Models\Business;
use App\Business\Services\BusinessDriveService;
use App\Services\GoogleDriveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

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
            'files' => ['required'],
            'files.*' => ['file', 'max:102400'],
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

        foreach ($request->file('files') as $file) {
            $this->businessDrive->uploadFileToDrive(
                $subfolderId,
                $file->getClientOriginalName(),
                file_get_contents($file->getRealPath()),
                $file->getMimeType() ?? 'application/octet-stream',
            );

            $uploaded[] = $file->getClientOriginalName();
        }

        return response()->json(['uploaded' => $uploaded]);
    }
}
