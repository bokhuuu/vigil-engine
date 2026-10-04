<?php

declare(strict_types=1);

namespace App\Business\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Correspondence extends Model
{
    protected $table = 'business_correspondence';

    protected $fillable = [
        'business_id',
        'round',
        'sent_at',
        'deadline_at',
        'received_at',
        'drive_folder_url',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'date',
            'deadline_at' => 'date',
            'received_at' => 'date',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
