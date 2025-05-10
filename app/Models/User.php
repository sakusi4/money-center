<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    protected $fillable = [
        'chat_id',
        'login_id',
        'password'
    ];

    public function generateAccessToken(): string
    {
        $this->access_token = Str::random(64);
        $this->save();
        return $this->access_token;
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }
}
