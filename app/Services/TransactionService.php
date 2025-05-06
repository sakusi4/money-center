<?php

namespace App\Services;

use App\Models\Transaction;
use Carbon\Carbon;
use App\Models\Budget;

class TransactionService
{
    /**
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function addExpense(int $userId, float $amount, string $desc): Transaction
    {
        $now = Carbon::now();

        $budget = Budget::where('user_id', $userId)
            ->where('year', $now->year)
            ->where('month', $now->month)
            ->firstOrFail();  // 예산이 없으면 ModelNotFoundException

        return Transaction::create([
            'budget_id' => $budget->id,
            'tx_date' => $now->toDateString(),
            'type' => 'expense',
            'amount' => $amount,
            'description' => $desc,
        ]);
    }

    /**
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function addIncome(int $userId, float $amount): Transaction
    {
        $now = Carbon::now();

        $budget = Budget::where('user_id', $userId)
            ->where('year', $now->year)
            ->where('month', $now->month)
            ->firstOrFail();

        return Transaction::create([
            'budget_id' => $budget->id,
            'tx_date' => $now->toDateString(),
            'type' => 'income',
            'amount' => $amount,
        ]);
    }
}
