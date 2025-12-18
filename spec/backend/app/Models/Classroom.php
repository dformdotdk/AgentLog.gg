<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    protected $table = 'classes';
    protected $fillable = ['teacher_id','season_id','class_code','name'];

    public function teacher(): BelongsTo { return $this->belongsTo(Teacher::class); }
    public function season(): BelongsTo { return $this->belongsTo(Season::class); }
    public function links(): HasMany { return $this->hasMany(ClassAgent::class, 'class_id'); }
}
