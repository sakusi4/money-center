<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>거래 수정</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800">

<div class="max-w-md mx-auto p-4">
    <h1 class="text-xl font-bold mb-4">✏️ 거래 수정</h1>

    <form method="POST" action="{{ route('transaction.update', $tx->id) }}?token={{ request('token') }}" class="bg-white p-4 rounded shadow space-y-3">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm mb-1">금액</label>
            <input type="number" step="0.01" name="amount" value="{{ $tx->amount }}" class="w-full border rounded px-3 py-1" required>
        </div>

        <div>
            <label class="block text-sm mb-1">설명</label>
            <input type="text" name="description" value="{{ $tx->description }}" class="w-full border rounded px-3 py-1" required>
        </div>

        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded">저장</button>
    </form>
</div>

</body>
</html>
