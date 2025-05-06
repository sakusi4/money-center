<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\{BudgetService, TransactionService, StatusService};
use App\Models\User;

class TelegramController extends Controller
{
    public function webhook(Request $request, BudgetService $budgetSvc, TransactionService $txSvc, StatusService $statusSvc)
    {
        $update = $request->all();
        $chatId = $update['message']['chat']['id'];
        $text = trim($update['message']['text'] ?? '');

        $user = User::firstOrCreate(
            ['chat_id' => $chatId],
            ['login_id' => 'tg_' . $chatId, 'password' => bcrypt(str()->random(12))]
        );

        [$command, $args] = $this->parseCommand($text);

        switch ($command) {
            case 'budget':
                [$amount] = $args;
                $budgetSvc->setBase($user->id, $amount);
                $reply = "예산이 {$amount}으로 설정됐어요 ✅";
                break;

            case 'expense':
                [$amount, $desc] = $args;
                $txSvc->addExpense($user->id, $amount, $desc);
                $reply = "지출 {$desc} {$amount} 기록 완료 ✍️";
                break;

            case 'income':
                [$amount] = $args;
                $txSvc->addIncome($user->id, $amount);
                $reply = "수입 {$amount} 추가 ✅";
                break;

            case '/상태':
                $reply = $this->formatStatus($statusSvc->summary($user->id));
                break;

            case '/내역':
                $reply = $this->listTransactions($user);
                break;

            default:
                $reply = "잘 이해하지 못했어요. 사용법: /예산, /지출, /수입, /상태, /내역 ⏰";
                break;
        }


//        Http::post("https://api.telegram.org/bot" . config('telegram.token') . "/sendMessage", [
//            'chat_id' => $chatId,
//            'text' => $reply,
//        ]);

//        return response()->json(['reply' => $reply]);
    }

    private function formatStatus(array $s): string
    {
        $slackEmoji = $s['slack'] < 0 ? '🔴' : '🟢';
        return <<<MSG
📊 이번 달 지출 현황
──────────────────
전체 사용 금액 : {$s['totalSpent']}
전체 남은 금액 : {$s['totalRemaining']}

오늘 사용 금액 : {$s['todaySpent']}
오늘 남은 금액 : {$s['todayRemaining']}

누적 여유 금액 : {$s['slack']} {$slackEmoji}
현재 사용 가능 : {$s['currentAvailable']}
MSG;
    }

    private function listTransactions(User $user): string
    {
        return $user->transactions()
            ->whereMonth('tx_date', now()->month)
            ->orderByDesc('tx_date')
            ->limit(20)
            ->get()
            ->map(fn($t) => "{$t->tx_date} {$t->description} {$t->amount}")
            ->implode("\n") ?: '이번 달 기록이 없습니다.';
    }

    private function parseCommand(string $text): array
    {
        $map = [
            'budget' => '/^\/예산\s+(\d+)/u',
            'expense' => '/^\/지출\s+(\d+)\s+(.+)/u',
            'income' => '/^\/수입\s+(\d+)/u',
        ];

        foreach ($map as $cmd => $pattern) {
            if (preg_match($pattern, $text, $m)) {
                array_shift($m); // $m[0] = 전체 매치, 버림
                return [$cmd, $m]; // ex) ['budget', ['5000']]
            }
        }

        return [$text, []]; // '/상태', '/내역' 등 literal 커맨드 처리
    }

}
