<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class WebViewController
{
    public function show($id, Request $request)
    {
        $user = $request->get('auth_user');
        $selectedMonth = $request->query('month') ?? now()->format('Y-m');
        [$year, $month] = explode('-', $selectedMonth);

        $availableMonths = Transaction::query()
            ->whereHas('budget', fn($q) => $q->where('user_id', $user->id))
            ->selectRaw('YEAR(tx_date) as year, MONTH(tx_date) as month')
            ->groupBy('year', 'month')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get()
            ->map(fn($t) => [
                'value' => sprintf('%04d-%02d', $t->year, $t->month),
                'label' => sprintf('%d년 %d월', $t->year, $t->month),
            ]);

        $budget = $user->budgets()
            ->where('year', $year)
            ->where('month', $month)
            ->with('transactions')
            ->firstOrFail();

        $transactions = $budget->transactions
            ->sortByDesc('created_at')
            ->sortByDesc('tx_date')
            ->groupBy(fn($t) => $t->tx_date);

        return view('mobile.detail', [
            'user' => $user,
            'budget' => $budget,
            'transactions' => $transactions,
            'availableMonths' => $availableMonths,
            'selectedMonth' => $selectedMonth,
            'selectedLabel' => "{$year}년 {$month}월",
        ]);
    }
}
