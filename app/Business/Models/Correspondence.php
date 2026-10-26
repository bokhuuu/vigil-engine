<?php

declare(strict_types=1);

namespace App\Business\Models;

use Database\Factories\Business\CorrespondenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Correspondence extends Model
{
    use HasFactory;

    protected $table = 'business_correspondence';

    protected $connection = 'business';

    protected $fillable = [
        'business_id',
        'round',
        'sent_at',
        'deadline_at',
        'received_at',
        'drive_folder_url',
        'notes',
        'status',
        'recipient_email',
        'cc_emails',
        'subject',
        'body',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'date',
            'deadline_at' => 'date',
            'received_at' => 'date',
            'cc_emails' => 'array',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    protected static function newFactory(): CorrespondenceFactory
    {
        return CorrespondenceFactory::new();
    }
}
