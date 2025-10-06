<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aggiornamento Ordine - CARDSWAP</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color:#111827; max-width: 640px; margin: 0 auto; padding: 24px; background:#f8fafc; }
        .card { background:#ffffff; border:1px solid #e5e7eb; border-radius: 12px; overflow:hidden; }
        .header { background:#1e40af; color:#fff; padding: 20px; text-align:center; }
        .content { padding: 20px; }
        .footer { background:#f1f5f9; color:#334155; padding: 16px; text-align:center; font-size: 12px; }
        .badge { display:inline-block; background:#e0e7ff; color:#3730a3; padding:4px 10px; border-radius:9999px; font-size:12px; }
        .muted { color:#64748b; font-size: 14px; }
        a.btn { display:inline-block; background:#1e40af; color:#fff !important; padding:10px 14px; border-radius:8px; text-decoration:none; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>📦 Aggiornamento Ordine</h1>
            <div class="badge">#{{ $order['order_number'] }} • {{ strtoupper($status) }}</div>
        </div>
        <div class="content">
            <p>Ciao {{ $buyer['name'] }},</p>
            <p>{{ $message }}</p>

            @if(!empty($tracking))
            <p class="muted">Tracking: <strong>{{ $tracking['number'] }}</strong></p>
            @if(!empty($tracking['url']))
            <p>
                <a class="btn" href="{{ $tracking['url'] }}" target="_blank" rel="noopener">Traccia spedizione</a>
            </p>
            @endif
            @endif

            <p class="muted">Ordine: #{{ $order['order_number'] }} • Totale: € {{ number_format($order['total_amount'], 2, ',', '.') }}</p>
        </div>
        <div class="footer">
            <p>CARDSWAP • Piattaforma di Scambio Carte</p>
            <p>Per assistenza: support@cardswap.com</p>
        </div>
    </div>
</body>
</html>


