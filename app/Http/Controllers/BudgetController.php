<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Services\BudgetService;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function update(Request $request, $id, BudgetService $budgetService)
    {
        $budgetService->setBase(
            $request->get('auth_user'),
            $request->input('base_amount')
        );

        return back()->with('success', '예산이 수정되었습니다.');
    }
}
