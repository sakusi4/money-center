<?php

namespace App\Services;

use App\Models\Transaction;
use Carbon\Carbon;

class TransactionService
{
    public function addExpense(int $userId, float $amount, string $desc): Transaction
    {
        return Transaction::create([
            'user_id' => $userId,
            'tx_date' => Carbon::now()->toDateString(),
            'type' => 'expense',
            'amount' => $amount,
            'description' => $desc,
        ]);
    }

    public function addIncome(int $userId, float $amount): Transaction
    {
        return Transaction::create([
            'user_id' => $userId,
            'tx_date' => Carbon::now()->toDateString(),
            'type' => 'income',
            'amount' => $amount,
        ]);
    }
}
