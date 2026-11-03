<?php

namespace App\Models;

use Database\Factories\DriverFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Driver extends Authenticatable
{
    /** @use HasFactory<DriverFactory> */
    use HasApiTokens, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'tricycle_number',
        'contact_number',
        'email',
        'password',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }
}
