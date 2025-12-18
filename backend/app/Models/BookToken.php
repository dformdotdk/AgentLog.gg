<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookToken extends Model
{
    use HasFactory;

    protected $fillable = ['season_id','token','is_active','max_activations','activation_count'];

    public function season(): BelongsTo { return $this->belongsTo(Season::class); }

    public function canActivate(): bool
    {
        if (!$this->is_active) return false;
        if ((int)$this->max_activations === 0) return true;
        return (int)$this->activation_count < (int)$this->max_activations;
    }
}
