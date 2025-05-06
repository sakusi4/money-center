<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    protected $fillable = [
        'chat_id',
        'login_id',
        'password'
    ];

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }
}
