<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Household extends Model
{
    use HasFactory;

    protected $fillable = ['settings','wizard_state','parent_pin_hash'];

    protected $casts = [
        'settings' => 'array',
    ];

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(ParentContact::class);
    }
}
