<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ActExcelEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExcelEntryController
{
    public function store(Request $request, string $recordId): JsonResponse
    {
        ActExcelEntry::firstOrCreate(
            ['act_record_id' => $recordId],
            ['entered_at' => now()],
        );

        return response()->json(['ok' => true]);
    }
}
