<?php

declare(strict_types=1);

namespace App\Business\Http\Controllers;

use App\Business\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $businesses = Business::with('correspondence')
            ->orderBy('lot_number')
            ->orderBy('name')
            ->get();

        $stats = [
            'total' => $businesses->count(),
            'active' => $businesses->where('status', 'active')->count(),
            'complete' => $businesses->where('status', 'complete')->count(),
            'pending' => $businesses->where('status', 'pending')->count(),
            'overdue' => $businesses->filter(fn ($b) => $b->correspondence
                ->where('status', 'overdue')->count() > 0)->count(),
        ];

        return view('business.dashboard', compact('businesses', 'stats'));
    }
}
