<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Series extends Model
{
    use HasFactory;

    protected $fillable = ['name','slug','theme_config'];

    protected $casts = [
        'theme_config' => 'array',
    ];

    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }
}
