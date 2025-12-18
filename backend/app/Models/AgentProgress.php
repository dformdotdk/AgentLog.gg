<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentProgress extends Model
{
    use HasFactory;

    protected $fillable = ['agent_id','mission_id','status','attempts_count','last_attempt_at','completed_at'];

    protected $casts = [
        'last_attempt_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
