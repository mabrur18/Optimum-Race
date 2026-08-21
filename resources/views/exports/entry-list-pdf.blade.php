<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Entry List - Optimum Race</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #1f2937; }
        h1 { color: #f97316; font-size: 18px; margin: 0 0 4px; }
        .meta { color: #6b7280; font-size: 10px; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f97316; color: #fff; font-weight: bold; text-align: center; font-size: 10px; }
        tr:nth-child(even) td { background: #fff7ed; }
        .nowrap { white-space: nowrap; }
        .footer { margin-top: 20px; color: #9ca3af; font-size: 9px; text-align: right; }
    </style>
</head>
<body>
    <h1>DAFTAR PESERTA (ENTRY LIST)</h1>
    <div class="meta">
        Optimum Race System &mdash; Export {{ $generatedAt }} &mdash; Total {{ count($rows) }} peserta
    </div>

    @if(empty($rows))
        <p>Tidak ada data yang sesuai filter.</p>
    @else
    <table>
        <thead>
            <tr>
                <th style="width:32px;">No</th>
                @foreach($headers as $h)
                    <th>{{ $h }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $idx => $row)
                <tr>
                    <td style="text-align:center;">{{ $idx + 1 }}</td>
                    @foreach(array_values($row) as $cell)
                        <td>{{ $cell === '' ? '-' : e($cell) }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="footer">
        Dicetak otomatis oleh Optimum Race Platform pada {{ $generatedAt }}
    </div>
</body>
</html>
