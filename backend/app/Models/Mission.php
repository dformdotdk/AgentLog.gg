<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mission extends Model
{
    use HasFactory;

    protected $fillable = [
        'season_id',
        'mission_no',
        'slug',
        'xp_reward',
        'is_boss',
        'topic_tags',
        'assets',
        'content',
        'validation',
    ];

    protected $casts = [
        'topic_tags' => 'array',
        'assets' => 'array',
        'content' => 'array',
        'validation' => 'array',
        'is_boss' => 'boolean',
    ];

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function missionVideos(): HasMany
    {
        return $this->hasMany(MissionVideo::class);
    }
}
