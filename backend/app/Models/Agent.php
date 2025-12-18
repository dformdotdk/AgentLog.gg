<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class Agent extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = ['household_id','agent_key'];

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function seasonStates(): HasMany
    {
        return $this->hasMany(AgentSeasonState::class);
    }
}
