<?php

// BudgetService.php
namespace App\Services;

use App\Models\Budget;
use Carbon\Carbon;

class BudgetService
{
    public function setBase(int $userId, float $amount): Budget
    {
        $now = Carbon::now();
        return Budget::updateOrCreate(
            [
                'user_id' => $userId,
                'year' => $now->year,
                'month' => $now->month
            ],
            [
                'base_amount' => $amount
            ]
        );
    }
}
