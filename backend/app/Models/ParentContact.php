<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'household_id',
        'email',
        'status',
        'verification_token',
        'unsub_token',
        'prefs',
        'verified_at',
    ];

    protected $casts = [
        'prefs' => 'array',
        'verified_at' => 'datetime',
    ];

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }
}
