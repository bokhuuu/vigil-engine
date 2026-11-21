<?php

declare(strict_types=1);

namespace App\Business\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessAlias extends Model
{
    protected $connection = 'business';

    protected $fillable = [
        'business_id',
        'name',
        'registration_code',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
