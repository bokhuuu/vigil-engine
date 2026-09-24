<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ActCompletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ActCompleteController
{
    public function __construct(
        private ActCompletionService $completion,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $recordId = (string) ($request->json('record_id') ?? '');

        if ($recordId === '') {
            Log::warning('Act complete webhook: no record_id in payload');

            return response()->json(['ok' => true]);
        }

        $missing = $this->completion->check($recordId);

        Log::info('Act complete webhook: checked', [
            'record_id' => $recordId,
            'result' => $missing === null ? 'not complete' : ($missing === [] ? 'verified' : $missing),
        ]);

        return response()->json(['ok' => true]);
    }
}
