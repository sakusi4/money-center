<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use App\Models\Budget;

class TransactionService
{
    private BudgetService $budgetService;
    public function __construct(BudgetService $budgetService){
        $this->budgetService = $budgetService;
    }

    public function addExpense(User $user, float $amount, string $desc): Transaction
    {
        $now = Carbon::now($user->timezone);

        $budget = Budget::where('user_id', $user->id)
            ->where('year', $now->year)
            ->where('month', $now->month)
            ->firstOrFail();

        return Transaction::create([
            'budget_id' => $budget->id,
            'tx_date' => $now->toDateString(),
            'type' => 'expense',
            'amount' => $amount,
            'description' => $desc,
        ]);
    }

    public function addIncome(User $user, float $amount, string $desc): Transaction
    {
        $now = Carbon::now();

        $budget = Budget::where('user_id', $user->id)
            ->where('year', $now->year)
            ->where('month', $now->month)
            ->firstOrFail();

        $this->budgetService->setBase($user, $budget->base_amount + $amount);

        return Transaction::create([
            'budget_id' => $budget->id,
            'tx_date' => $now->toDateString(),
            'type' => 'income',
            'amount' => $amount,
            'description' => $desc,
        ]);
    }
}
