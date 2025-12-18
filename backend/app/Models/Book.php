<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory;

    protected $fillable = ['series_id','name','slug','subject','grade_band','language'];

    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }

    public function seasons(): HasMany
    {
        return $this->hasMany(Season::class);
    }
}
