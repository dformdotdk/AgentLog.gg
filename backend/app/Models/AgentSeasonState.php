<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentSeasonState extends Model
{
    use HasFactory;

    protected $fillable = ['agent_id','season_id','xp_total','level','paired_mode','paired_ref'];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }
}
