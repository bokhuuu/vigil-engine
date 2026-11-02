<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActExcelEntry extends Model
{
    protected $connection = 'business';

    protected $table = 'act_excel_entries';

    protected $fillable = [
        'act_record_id',
        'entered_at',
    ];

    protected $casts = [
        'entered_at' => 'datetime',
    ];
}
