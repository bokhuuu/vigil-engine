<?php

declare(strict_types=1);

namespace App\Business\Http\Controllers;

use App\Business\Models\Business;
use App\Business\Models\Correspondence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CorrespondenceController extends Controller
{
    public function store(Business $business): RedirectResponse
    {
        $business->update(['status' => 'მიმდინარე']);

        return redirect()->route('business.show', $business)->with('success', 'წერილი გაგზავნილად მოინიშნა');
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
