<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\{BudgetService, TransactionService, StatusService};
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TelegramController extends Controller
{
    public function webhook(Request $request, BudgetService $budgetSvc, TransactionService $txSvc, StatusService $statusSvc)
    {
        $update = $request->all();
        Log::info('telegram.update', $update);

        try {
            $chatId = data_get($update, 'message.chat.id');
            $text = trim(data_get($update, 'message.text', ''));

            if (!$chatId || $text === '') {
                Log::warning('telegram.empty_payload', $update);
                return response()->noContent();
            }

            $user = User::firstOrCreate(
                ['chat_id' => $chatId],
                ['login_id' => 'tg_' . $chatId, 'password' => bcrypt(str()->random(12))]
            );

            [$command, $args] = $this->parseCommand($text);

            switch ($command) {
                case 'start':
                    $reply = $this->welcomeMessage();
                    break;

                case 'budget':
                    [$amount] = $args;
                    $budgetSvc->setBase($user->id, $amount);
                    $reply = "예산이 {$amount}으로 설정됐어요 ✅";
                    break;

                case 'expense':
                    try {
                        [$amount, $desc] = $args;
                        $txSvc->addExpense($user->id, $amount, $desc);
                        $reply = "지출 {$desc} {$amount} 기록 완료 ✍️";
                    } catch (ModelNotFoundException $e) {
                        $reply = "❗️ 이번 달 예산이 아직 없습니다.\n/예산 [금액] 으로 먼저 예산을 설정해 주세요.";
                    }
                    break;

//                case 'income':
//                    try {
//                        [$amount] = $args;
//                        $txSvc->addIncome($user->id, $amount);
//                        $reply = "수입 {$amount} 추가 ✅";
//                    } catch (ModelNotFoundException $e) {
//                        $reply = "❗️ 이번 달 예산이 아직 없습니다.\n/예산 [금액] 으로 먼저 예산을 설정해 주세요.";
//                    }
//                    break;

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

//            return response()->json(['data' => $reply]);
            Http::post("https://api.telegram.org/bot" . config('telegram.token') . "/sendMessage", [
                'chat_id' => $chatId,
                'text' => $reply,
                'parse_mode' => 'Markdown',
            ]);
        } catch (\Throwable $e) {
            Log::error('telegram.exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if (isset($chatId)) {
                Http::post("https://api.telegram.org/bot" . config('telegram.token') . "/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => "⚠️ 알 수 없는 오류가 발생했습니다. 잠시 후 다시 시도해 주세요.",
                ]);
            }
        }
    }

    private function formatStatus(array $s): string
    {
        $fmt = fn($v) => number_format((float)$v, 2);
        $slackEmoji = $s['slack'] < 0 ? '🔴' : '🟢';

        return <<<MSG
📊 이번 달 지출 현황
──────────────────
일일 평균 사용 가능 금액 (예산/일수) : {$fmt($s['$dailyAllowance'])}

전체 사용 금액 : {$fmt($s['totalSpent'])}
전체 남은 금액 : {$fmt($s['totalRemaining'])}

오늘 사용 금액 : {$fmt($s['todaySpent'])}
오늘 남은 금액 : {$fmt($s['todayRemaining'])}

누적 여유 금액 : {$fmt($s['slack'])} {$slackEmoji}
현재 사용 가능 : {$fmt($s['currentAvailable'])}
MSG;
    }

    /**
     * Friendly welcome message for first-time and /start command.
     */
    private function welcomeMessage(): string
    {
        return <<<TXT
👋 안녕하세요! *가계부 챗봇*에 오신 것을 환영합니다.

*사용 방법*
────────────
`/예산 5000`  — 이번 달 예산 설정 또는 수정
`/지출 300 점심`  — 오늘 지출 기록 (금액 설명)
`/상태`  — 예산·잔액·여유 금액 요약
`/내역`  — 이번 달 지출 내역 조회

언제든지 `/start` 를 입력하면 이 도움말을 다시 볼 수 있어요.
즐거운 소비 관리가 되길 바랍니다!
TXT;
    }

    private function listTransactions(User $user): string
    {
        $now = Carbon::now();

        // 이번 달 예산
        $budget = Budget::where('user_id', $user->id)
            ->where('year', $now->year)
            ->where('month', $now->month)
            ->firstOrFail();

        $limit = (float)($budget->avg_available_amount ?? 0);

        $txs = $budget->transactions()
            ->orderBy('tx_date')
            ->get()
            ->groupBy('tx_date');

        if ($txs->isEmpty()) {
            return '이번 달 기록이 없습니다.';
        }

        $lines = [];

        foreach ($txs as $date => $items) {
            foreach ($items as $t) {
                $lines[] = sprintf(
                    '%s %s %s',
                    $date,
                    $t->description,
                    number_format($t->amount, 2)
                );
            }


            $dailySpent = $items->where('type', 'expense')->sum('amount');
            $diff = $dailySpent - $limit;

            if ($diff > 0) {
                $summary = '🔴 초과 +' . number_format($diff, 2);
            } elseif ($diff < 0) {
                $summary = '🟢 절약 ' . number_format(abs($diff), 2);
            } else {
                $summary = '⚪️ 정확히 사용';
            }

            $lines[] = sprintf('└ 하루 합계: %s (%s)', number_format($dailySpent, 2), $summary);
            $lines[] = '────────────';
        }

        // 마지막 구분선 제거
        array_pop($lines);

        return implode("\n", $lines);
    }

    /**
     * Parse incoming text message and return [command, args].
     * args format
     *   - budget  : [amount]
     *   - income  : [amount]
     *   - expense : [amount, description]
     */
    private function parseCommand(string $text): array
    {
        // 1) /start
        if (preg_match('/^\/start$/i', $text)) {
            return ['start', []];
        }

        // 2) /예산 5000
        if (preg_match('/^\/예산\s+([\d]+(?:\.\d+)?)/u', $text, $m)) {
            return ['budget', [$m[1]]];
        }

        // 3) /수입 1000.50
//        if (preg_match('/^\/수입\s+([\d]+(?:\.\d+)?)/u', $text, $m)) {
//            return ['income', [$m[1]]];
//        }

        // 4) /지출 (amount first OR amount last)
        if (preg_match('/^\/지출\s+(.+)/u', $text, $m)) {
            $payload = trim($m[1]);

            // case A: amount first -> "/지출 300 점심"
            if (preg_match('/^([\d]+(?:\.\d+)?)\s+(.+)/u', $payload, $p)) {
                return ['expense', [$p[1], $p[2]]];
            }

            // case B: description first -> "/지출 구글원 6.33"
            if (preg_match('/^(.+)\s+([\d]+(?:\.\d+)?)/u', $payload, $p)) {
                return ['expense', [$p[2], $p[1]]];
            }

            return ['error', []];
        }

        return [$text, []];
    }

}
