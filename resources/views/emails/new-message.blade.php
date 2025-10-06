<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nuovo Messaggio - CARDSWAP</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color:#111827; max-width: 640px; margin: 0 auto; padding: 24px; background:#f8fafc; }
        .card { background:#ffffff; border:1px solid #e5e7eb; border-radius: 12px; overflow:hidden; }
        .header { background:#1e40af; color:#fff; padding: 20px; text-align:center; }
        .content { padding: 20px; }
        .footer { background:#f1f5f9; color:#334155; padding: 16px; text-align:center; font-size: 12px; }
        a.btn { display:inline-block; background:#1e40af; color:#fff !important; padding:10px 14px; border-radius:8px; text-decoration:none; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>💬 Nuovo Messaggio</h1>
        </div>
        <div class="content">
            <p>Hai ricevuto un nuovo messaggio sull'ordine #{{ $order_number }} da {{ $sender_name }}:</p>
            <blockquote>{{ $message_preview }}</blockquote>
            <p>
                <a class="btn" href="{{ $conversation_url }}" target="_blank" rel="noopener">Apri conversazione</a>
            </p>
        </div>
        <div class="footer">
            <p>CARDSWAP • Piattaforma di Scambio Carte</p>
        </div>
    </div>
</body>
</html>


