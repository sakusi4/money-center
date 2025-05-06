<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int|mixed $user_id
 * @property float|mixed $avg_available_amount
 * @property int|mixed $year
 * @property int|mixed $month
 * @property float|mixed $base_amount
 */
class Budget extends Model
{
    protected $fillable = [
        'user_id',
        'year',
        'month',
        'base_amount',
        'avg_available_amount'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
