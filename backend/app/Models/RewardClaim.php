<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardClaim extends Model
{
    use HasFactory;

    protected $fillable = ['household_id','reward_id','agent_id','season_id','available_at','redeemed_at'];

    protected $casts = [
        'available_at' => 'datetime',
        'redeemed_at' => 'datetime',
    ];

    public function reward(): BelongsTo
    {
        return $this->belongsTo(Reward::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
