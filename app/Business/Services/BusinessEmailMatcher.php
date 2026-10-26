<?php

declare(strict_types=1);

namespace App\Business\Services;

use App\Business\Models\Business;
use Illuminate\Support\Collection;

class BusinessEmailMatcher
{
    private Collection $businesses;

    public function __construct()
    {
        $this->businesses = Business::on('business')->get(['id', 'name', 'registration_code']);
    }

    public function match(string $subject, string $body): ?Business
    {
        // Priority 1: match by registration code in subject
        preg_match_all('/\b\d+\b/', $subject, $matches);

        foreach ($matches[0] as $code) {
            $business = $this->businesses->firstWhere('registration_code', $code);
            if ($business) {
                return Business::on('business')->find($business->id);
            }
        }

        // Priority 2: match by business name in subject
        foreach ($this->businesses as $business) {
            if (mb_stripos($subject, $business->name) !== false) {
                return Business::on('business')->find($business->id);
            }
        }

        return null;
    }

    public function matchedBy(string $subject, string $body): string
    {
        preg_match_all('/\b\d+\b/', $subject, $matches);
        foreach ($matches[0] as $code) {
            if ($this->businesses->firstWhere('registration_code', $code)) {
                return 'registration_code';
            }
        }

        foreach ($this->businesses as $business) {
            if (mb_stripos($subject, $business->name) !== false) {
                return 'name';
            }
        }

        return 'unmatched';
    }
}
