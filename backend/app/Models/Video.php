<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Video extends Model
{
    use HasFactory;

    protected $fillable = ['title','provider','provider_id','duration_seconds','subtitles'];

    protected $casts = [
        'subtitles' => 'array',
    ];

    public function missionVideos(): HasMany
    {
        return $this->hasMany(MissionVideo::class);
    }
}
