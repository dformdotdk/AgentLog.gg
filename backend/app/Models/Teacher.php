<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = ['email','password','school_name'];
    protected $hidden = ['password'];

    public function classes(): HasMany
    {
        return $this->hasMany(Classroom::class, 'teacher_id');
    }
}
