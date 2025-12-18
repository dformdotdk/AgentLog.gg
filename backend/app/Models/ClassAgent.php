<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassAgent extends Model
{
    use HasFactory;

    protected $fillable = ['class_id','agent_id','linked_at','unlinked_at'];
    protected $casts = ['linked_at'=>'datetime','unlinked_at'=>'datetime'];

    public function classroom(): BelongsTo { return $this->belongsTo(Classroom::class, 'class_id'); }
    public function agent(): BelongsTo { return $this->belongsTo(Agent::class); }
}
