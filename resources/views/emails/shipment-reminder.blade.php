<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Promemoria Spedizione - CARDSWAP</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color:#111827; max-width: 640px; margin: 0 auto; padding: 24px; background:#f8fafc; }
        .card { background:#ffffff; border:1px solid #e5e7eb; border-radius: 12px; overflow:hidden; }
        .header { background:#ea580c; color:#fff; padding: 20px; text-align:center; }
        .content { padding: 20px; }
        .footer { background:#f1f5f9; color:#334155; padding: 16px; text-align:center; font-size: 12px; }
        .badge { display:inline-block; background:#ffedd5; color:#9a3412; padding:4px 10px; border-radius:9999px; font-size:12px; }
        a.btn { display:inline-block; background:#1e40af; color:#fff !important; padding:10px 14px; border-radius:8px; text-decoration:none; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>⏰ Promemoria Spedizione</h1>
            <div class="badge">Ordine #{{ $order['order_number'] }}</div>
        </div>
        <div class="content">
            <p>Ciao {{ $seller['name'] }},</p>
            <p>ti ricordiamo di spedire l'ordine #{{ $order['order_number'] }} per {{ $buyer['name'] }}.</p>
            <p>Totale ordine: € {{ number_format($order['total_amount'], 2, ',', '.') }}</p>

            <p>
                <a class="btn" href="{{ $dashboard_url }}" target="_blank" rel="noopener">Apri ordine nel pannello venditore</a>
            </p>
        </div>
        <div class="footer">
            <p>CARDSWAP • Piattaforma di Scambio Carte</p>
        </div>
    </div>
</body>
</html>


