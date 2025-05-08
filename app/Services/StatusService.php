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
        $daysPassedSinceStart = $budgetStart->diffInDays($now->copy()->startOfDay());

        $shouldHaveSpent = $budget->avg_available_amount * $daysPassedSinceStart;

        $spentToday = Transaction::where('budget_id', $budget->id)
            ->where('tx_date', $today)
            ->where('type', 'expense')
            ->sum('amount');

        $remainingToday = $budget->avg_available_amount - $spentToday;
        $slack = $shouldHaveSpent - $spentTotal;
        $currentAvailable = $remainingToday + $slack;

        if ($slack < 0 && $remainingToday > 0) {
            if (abs($slack) > $remainingToday) {
                $slack += $remainingToday;
                $remainingToday = 0;
            } else {
                $remainingToday += $slack;
                $slack = 0;
            }
        }

        return [
            '$budget' => $budget->base_amount,
            '$dailyAllowance' => $budget->avg_available_amount,
            'totalSpent' => $spentTotal,
            'totalRemaining' => $remainingTot,
            'todaySpent' => $spentToday,
            'todayRemaining' => $remainingToday,
            'slack' => $slack,
            'currentAvailable' => ($currentAvailable) > 0 ? $currentAvailable : 0,
        ];
    }
}
