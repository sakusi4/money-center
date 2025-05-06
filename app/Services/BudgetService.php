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

        $budget = Budget::where([
            'user_id' => $userId,
            'year' => $now->year,
            'month' => $now->month
        ])->first();

        $baseBudget = $amount;
        $budgetStart = ($budget) ? $budget->created_at->copy()->startOfDay() : $now->copy()->startOfDay();

        $daysActive = (int) $budgetStart->diffInDays($now->copy()->endOfMonth(), true, false) + 1;
        $dailyAllowance = $daysActive > 0 ? $baseBudget / $daysActive : 0;

        if ($budget) {
            $budget->base_amount = $amount;
            $budget->avg_available_amount = $dailyAllowance;
            $budget->save();

        } else {
            $budget = new Budget();
            $budget->user_id = $userId;
            $budget->year = $now->year;
            $budget->month = $now->month;
            $budget->base_amount = $amount;
            $budget->avg_available_amount = $dailyAllowance;
            $budget->save();
        }

        return $budget;
    }
}
