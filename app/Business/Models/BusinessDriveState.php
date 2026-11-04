<?php

declare(strict_types=1);

namespace App\Business\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessDriveState extends Model
{
    protected $connection = 'business';

    protected $fillable = [
        'business_id',
        'subfolder_name',
        'has_files',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'has_files' => 'boolean',
            'synced_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
