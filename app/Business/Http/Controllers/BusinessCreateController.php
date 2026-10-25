<?php

declare(strict_types=1);

namespace App\Business\Http\Controllers;

use App\Business\Models\Business;
use App\Business\Services\BusinessDriveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class BusinessCreateController extends Controller
{
    public function __construct(private readonly BusinessDriveService $driveService) {}

    public function create(): View
    {
        return view('business.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'registration_code' => ['required', 'string', 'max:20', 'unique:business.businesses,registration_code'],
            'act_number' => ['nullable', 'string', 'max:20'],
            'lot_number' => ['required', 'integer', 'min:1'],
            'cadastral_code' => ['nullable', 'string', 'max:50'],
            'type' => ['required', 'array', 'min:1'],
            'type.*' => ['required', 'in:მეპატრონე,მოიჯარე,მეიჯარე,სხვა'],
            'status' => ['required', 'in:active,pending,complete'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'owner_phone' => ['nullable', 'string', 'max:50'],
            'owner_email' => ['nullable', 'email', 'max:255'],
            'accountant_name' => ['nullable', 'string', 'max:255'],
            'accountant_phone' => ['nullable', 'string', 'max:50'],
            'accountant_email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string'],
            'drive_folder_url' => ['nullable', 'url'],
        ]);

        $business = Business::create($validated);

        if (empty($validated['drive_folder_url'])) {
            $this->driveService->createBusinessFolder($business);
        }

        return redirect()->route('business.show', $business)->with('success', 'ბიზნესი დაემატა');
    }
}
