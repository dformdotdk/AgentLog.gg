<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionVideo extends Model
{
    use HasFactory;

    protected $fillable = ['mission_id','video_id','type','sort_order','parent_only','teacher_only'];

    protected $casts = [
        'parent_only' => 'boolean',
        'teacher_only' => 'boolean',
    ];

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }
}
