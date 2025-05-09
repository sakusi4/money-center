<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;

class StatusService
{
    public function summary(User $user): array
    {
        $now = Carbon::now($user->timezone);
        $today = $now->toDateString();

        $budget = Budget::where('user_id', $user->id)
            ->where('year', $now->year)
            ->where('month', $now->month)
            ->firstOrFail();

        $tx = Transaction::where('budget_id', $budget->id)->get();

        $spentTotal = $tx->where('type', 'expense')->sum('amount');

        $baseBudget = $budget?->base_amount ?? 0;
        $remainingTot = $baseBudget - $spentTotal;

        $budgetStart = $budget->created_at->tz($user->timezone)->startOfDay();
        $daysPassedSinceStart = $budgetStart->diffInDays($now->copy()->startOfDay()) + 1;

        $shouldHaveSpent = $budget->avg_available_amount * $daysPassedSinceStart;

        $spentToday = Transaction::where('budget_id', $budget->id)
            ->where('tx_date', $today)
            ->where('type', 'expense')
            ->sum('amount');

        $remainingToday = $shouldHaveSpent - $spentTotal;

        return [
            '$budget' => $budget->base_amount,
            '$dailyAllowance' => $budget->avg_available_amount,
            'totalSpent' => $spentTotal,
            'totalRemaining' => $remainingTot,
            'todaySpent' => $spentToday,
            'todayRemaining' => $remainingToday,
        ];
    }
}
