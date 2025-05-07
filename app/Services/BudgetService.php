<?php

// BudgetService.php
namespace App\Services;

use App\Models\Budget;
use App\Models\User;
use Carbon\Carbon;

class BudgetService
{
    public function setBase(User $user, float $amount): Budget
    {
        $now = Carbon::now($user->timezone);

        $budget = Budget::where([
            'user_id' => $user->id,
            'year' => $now->year,
            'month' => $now->month
        ])->first();

        $baseBudget = $amount;
        $budgetStart = ($budget) ? $budget->created_at->tz($user->timezone)->startOfDay() : $now->copy()->startOfDay();

        $daysActive = (int) $budgetStart->diffInDays($now->copy()->endOfMonth(), true, false) + 1;
        $dailyAllowance = $daysActive > 0 ? $baseBudget / $daysActive : 0;

        if ($budget) {
            $budget->base_amount = $amount;
            $budget->avg_available_amount = $dailyAllowance;
            $budget->save();

        } else {
            $budget = new Budget();
            $budget->user_id = $user->id;
            $budget->year = $now->year;
            $budget->month = $now->month;
            $budget->base_amount = $amount;
            $budget->avg_available_amount = $dailyAllowance;
            $budget->save();
        }

        return $budget;
    }
}
