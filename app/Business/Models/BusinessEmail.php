<?php

declare(strict_types=1);

namespace App\Business\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessEmail extends Model
{
    protected $connection = 'business';

    protected $fillable = [
        'business_id',
        'gmail_message_id',
        'email_from',
        'email_subject',
        'matched_by',
        'drive_folder_url',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
