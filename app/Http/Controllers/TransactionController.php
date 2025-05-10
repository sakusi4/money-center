<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function edit($id)
    {
        $tx = Transaction::findOrFail($id);
        return view('mobile.transaction_edit', compact('tx'));
    }

    public function update(Request $request, $id)
    {
        $tx = Transaction::findOrFail($id);
        $tx->amount = $request->input('amount');
        $tx->description = $request->input('description');
        $tx->save();

        return redirect()->to('/detail/' . $tx->budget->user_id . '?token=' . $request->query('token'))
            ->with('success', '거래가 수정되었습니다.');
    }

    public function destroy(Request $request, $id)
    {
        $tx = Transaction::findOrFail($id);
        $userId = $tx->budget->user_id;

        $tx->delete();

        return redirect()->to("/detail/{$userId}?token={$request->query('token')}")
            ->with('success', '거래가 삭제되었습니다.');
    }
}
