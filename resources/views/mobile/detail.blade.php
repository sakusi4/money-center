<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>내 지출 내역</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800">

<div class="max-w-md mx-auto p-4">
    <div class="mb-4">
        <form method="GET" action="">
            <label for="month" class="block text-sm mb-1">📅 조회할 달 선택:</label>
            <select name="month" id="month" onchange="this.form.submit()" class="w-full border px-2 py-1 rounded">
                @foreach ($availableMonths as $ym)
                    <option value="{{ $ym['value'] }}" {{ $ym['value'] === $selectedMonth ? 'selected' : '' }}>
                        {{ $ym['label'] }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="bg-white p-4 rounded-lg shadow mb-4">
        <form method="POST" action="{{ route('budget.update', $budget->id) }}?token={{ $user->access_token }}" class="space-y-2">
            @csrf
            @method('PUT')
            <p>📅 {{ $selectedLabel }}</p>

            <div>
                <label class="text-sm text-gray-500">💰 예산:</label>
                <input type="number" step="0.01" name="base_amount" value="{{ $budget->base_amount }}"
                       class="border rounded px-2 py-1 w-full mt-1" />
            </div>

            <p>📈 일일 한도: <span class="font-semibold">{{ number_format($budget->avg_available_amount, 2) }}원</span></p>

            <button type="submit" class="w-full bg-blue-500 text-white py-1 rounded mt-2">예산 수정</button>
        </form>

        <div class="mt-4 text-sm text-gray-700">
            <p>총 지출: <span class="font-semibold text-red-500">{{ number_format($budget->transactions->where('type', 'expense')->sum('amount'), 2) }}원</span></p>
            <p>총 남은 금액: <span class="font-semibold">{{ number_format($budget->base_amount + $budget->transactions->where('type', 'income')->sum('amount') - $budget->transactions->where('type', 'expense')->sum('amount'), 2) }}원</span></p>
        </div>
    </div>

    @foreach ($transactions as $date => $list)
        <div class="mb-3">
            <h2 class="text-sm text-gray-500 font-medium">{{ $date }}</h2>

            @foreach ($list as $tx)
                <div class="flex justify-between items-center bg-white rounded px-3 py-2 mt-1 shadow-sm">
                    <div>
                        <p class="font-medium">{{ $tx->description }}</p>
                        <p class="text-xs text-gray-400">{{ $tx->type }} | {{ $tx->created_at->format('H:i:s') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold {{ $tx->type === 'expense' ? 'text-red-500' : 'text-green-500' }}">
                            {{ number_format($tx->amount, 2) }}원
                        </p>
                        <div class="text-xs text-blue-600 mt-1 flex gap-2">
                            <a href="{{ route('transaction.edit', $tx->id) }}?token={{ $user->access_token }}">✏️</a>
                            <form action="{{ route('transaction.destroy', $tx->id) }}?token={{ $user->access_token }}" method="POST" onsubmit="return confirm('정말 삭제할것인가요?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit">🗑</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach
</div>

</body>
</html>
