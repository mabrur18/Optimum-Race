<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }} | ORS</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; color: #111827; line-height: 1.6; background: #f8fafc; padding: 24px; }
        .wrap { max-width: 760px; margin: 0 auto; background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden; }
        .header { background: linear-gradient(135deg, #f97316, #ea580c); color: #fff; padding: 24px 28px; }
        .header h1 { margin: 0; font-size: 22px; letter-spacing: 0.2px; }
        .header p { margin: 6px 0 0; font-size: 13px; opacity: 0.9; }
        .body { padding: 28px; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; }
        .label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #6b7280; margin-bottom: 4px; }
        .value { font-size: 15px; font-weight: 600; color: #0f172a; word-break: break-word; }
        .items { margin-top: 24px; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; }
        .items thead { background: #f1f5f9; }
        .items th, .items td { padding: 10px 14px; text-align: left; font-size: 13px; border-bottom: 1px solid #eef2f7; }
        .items th { font-weight: 600; color: #334155; }
        .items tfoot td { font-weight: 700; color: #0f172a; background: #fff7ed; padding: 12px 14px; }
        .cta { margin-top: 26px; padding: 18px 20px; border-radius: 12px; background: #ecfdf5; border: 1px solid #a7f3d0; }
        .cta a { display: inline-block; padding: 10px 16px; border-radius: 8px; background: #059669; color: #fff; text-decoration: none; font-weight: 600; }
        .footer { margin-top: 24px; padding: 16px 28px 24px; font-size: 12px; color: #6b7280; text-align: center; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="header">
            <h1>ORS &mdash; Optimum Racing System</h1>
            <p>Invoice Pendaftaran Event &mdash; {{ $event?->title ?? 'Event' }}</p>
        </div>
        <div class="body">
            <div class="grid">
                <div>
                    <div class="label">Nomor Invoice</div>
                    <div class="value">{{ $invoice->invoice_number }}</div>
                </div>
                <div>
                    <div class="label">Tanggal</div>
                    <div class="value">{{ $invoice->created_at?->format('d F Y, H:i') ?? '-' }}</div>
                </div>
                <div>
                    <div class="label">Nama Rider</div>
                    <div class="value">{{ $registration?->full_name ?? $registration?->rider?->full_name ?? '-' }}</div>
                </div>
                <div>
                    <div class="label">Tim</div>
                    <div class="value">{{ $registration?->team_name ?? '-' }}</div>
                </div>
                <div>
                    <div class="label">No. HP</div>
                    <div class="value">{{ $registration?->phone ?? $registration?->rider?->phone ?? '-' }}</div>
                </div>
                <div>
                    <div class="label">Email</div>
                    <div class="value">{{ $registration?->email ?? $registration?->rider?->email ?? '-' }}</div>
                </div>
            </div>

            <table class="items" style="width:100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="width:56%;">Kelas yang diikuti</th>
                        <th style="width:18%;">Nomor Start</th>
                        <th style="width:10%;">Utama</th>
                        <th style="width:16%; text-align:right;">Harga</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalFromItems = 0;
                        $items = $registration?->items ?? collect([]);
                    @endphp
                    @foreach ($items as $item)
                        @php
                            $price = (float) ($item->raceClass?->price ?? 0);
                            $totalFromItems += $price;
                        @endphp
                        <tr>
                            <td>{{ $item->raceClass?->name ?? 'Kelas' }}</td>
                            <td>{{ $item->start_number ?? '-' }}</td>
                            <td>{{ !empty($item->is_main_class) ? 'Ya' : 'Tambahan' }}</td>
                            <td style="text-align:right;">Rp {{ number_format($price, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    @if ($items->count() === 0)
                        <tr>
                            <td colspan="4" style="text-align:center; color:#6b7280; padding:14px;">Belum ada item kelas.</td>
                        </tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3">TOTAL BAYAR</td>
                        <td style="text-align:right;">{{ $total_formatted }}</td>
                    </tr>
                </tfoot>
            </table>

            <div class="cta">
                <div style="font-size:13px; color:#065f46; font-weight:600; margin-bottom:8px;">
                    Selesaikan pembayaran sesuai nominal di atas, lalu klik tombol di bawah untuk mengunggah bukti transfer:
                </div>
                <a href="{{ $payment_confirm_url }}">Konfirmasi Bukti Pembayaran &rarr;</a>
                <div style="margin-top:10px; font-size:12px; color:#047857;">
                    URL manual: <span style="word-break: break-all;">{{ $payment_confirm_url }}</span>
                </div>
            </div>
        </div>
        <div class="footer">
            Email ini dikirim otomatis oleh sistem ORS (Optimum Racing System) maksimal 5 menit setelah pendaftaran awal. Jika tidak menemukan email ini di Inbox, periksa folder Spam / Promosi.
        </div>
    </div>
</body>
</html>
