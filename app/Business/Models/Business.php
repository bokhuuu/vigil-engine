<?php

declare(strict_types=1);

namespace App\Business\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Business extends Model
{
    protected $connection = 'business';

    protected $fillable = [
        'name',
        'registration_code',
        'lot_number',
        'status',
        'drive_folder_url',
    ];

    public function correspondence(): HasMany
    {
        return $this->hasMany(Correspondence::class);
    }

    public function emails(): HasMany
    {
        return $this->hasMany(BusinessEmail::class);
    }
}
