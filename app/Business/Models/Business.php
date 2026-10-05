<?php

declare(strict_types=1);

namespace App\Business\Models;

use Database\Factories\Business\BusinessFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Business extends Model
{
    use HasFactory;

    protected $connection = 'business';

    protected $fillable = [
        'name',
        'registration_code',
        'act_number',
        'lot_number',
        'type',
        'status',
        'drive_folder_url',
        'owner_name',
        'owner_phone',
        'owner_email',
        'accountant_name',
        'accountant_phone',
        'accountant_email',
        'notes',
    ];

    public function correspondence(): HasMany
    {
        return $this->hasMany(Correspondence::class);
    }

    public function emails(): HasMany
    {
        return $this->hasMany(BusinessEmail::class);
    }

    protected static function newFactory(): BusinessFactory
    {
        return BusinessFactory::new();
    }
}
