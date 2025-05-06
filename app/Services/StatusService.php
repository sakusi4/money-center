<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Transaction;
use Carbon\Carbon;

class StatusService
{
    public function summary(int $userId): array
    {
        $now = Carbon::now();
        $today = $now->toDateString();

        $budget = Budget::where('user_id', $userId)
            ->where('year', $now->year)
            ->where('month', $now->month)
            ->first();

        $tx = Transaction::where('budget_id', $budget->id)->get();

        $spentTotal = $tx->where('type', 'expense')->sum('amount');

        $baseBudget = $budget?->base_amount ?? 0;
        $remainingTot = $baseBudget - $spentTotal;

        $budgetStart = $budget->created_at->copy()->startOfDay();
        $daysPassedSinceStart = $budgetStart->diffInDays($now->copy()->startOfDay());

        $shouldHaveSpent = $budget->avg_available_amount * $daysPassedSinceStart;

        $slack = $shouldHaveSpent - $spentTotal;

        $spentToday = Transaction::where('budget_id', $budget->id)
            ->where('tx_date', $today)
            ->where('type', 'expense')
            ->sum('amount');

        $remainingToday = $budget->avg_available_amount - $spentToday;

        return [
            '$dailyAllowance' => $budget->avg_available_amount,
            'totalSpent' => $spentTotal,
            'totalRemaining' => $remainingTot,
            'todaySpent' => $spentToday,
            'todayRemaining' => $remainingToday,
            'slack' => $slack,
            'currentAvailable' => $remainingToday + $slack,
        ];
    }
}
