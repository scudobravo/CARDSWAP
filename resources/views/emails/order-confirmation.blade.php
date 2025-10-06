<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Conferma Ordine - CARDSWAP</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #111827; max-width: 640px; margin: 0 auto; padding: 24px; background:#f8fafc; }
        .card { background:#ffffff; border:1px solid #e5e7eb; border-radius: 12px; overflow:hidden; }
        .header { background:#1e40af; color:#fff; padding: 20px; text-align:center; }
        .content { padding: 20px; }
        .footer { background:#f1f5f9; color:#334155; padding: 16px; text-align:center; font-size: 12px; }
        .section { margin-top: 16px; }
        .items-table { width:100%; border-collapse: collapse; margin-top: 8px; }
        .items-table th, .items-table td { text-align:left; padding: 10px; border-bottom:1px solid #e5e7eb; font-size: 14px; }
        .badge { display:inline-block; background:#e0e7ff; color:#3730a3; padding:4px 10px; border-radius:9999px; font-size:12px; }
        .total { font-weight: bold; font-size: 16px; }
    </style>
    </head>
<body>
    <div class="card">
        <div class="header">
            <h1>✅ Ordine Confermato</h1>
            <div class="badge">Ordine #{{ $order['order_number'] }}</div>
        </div>
        <div class="content">
            <p>Ciao {{ $buyer['name'] }},</p>
            <p>abbiamo ricevuto il tuo pagamento. Il tuo ordine è stato confermato e verrà preparato dai venditori.</p>

            <div class="section">
                <h3>Riepilogo Ordine</h3>
                <table class="items-table" role="presentation">
                    <thead>
                        <tr>
                            <th>Articolo</th>
                            <th style="width:80px">Qtà</th>
                            <th style="width:120px">Prezzo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                        <tr>
                            <td>{{ $item['title'] ?? ($item['card_model'] ?? 'Carta') }} @if(!empty($item['condition'])) <span class="badge">{{ $item['condition'] }}</span> @endif</td>
                            <td>{{ $item['quantity'] }}</td>
                            <td>€ {{ number_format($item['total_price'], 2, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="section">
                <p>Subtotale: € {{ number_format($totals['subtotal'], 2, ',', '.') }}<br>
                Spedizione: € {{ number_format($totals['shipping'], 2, ',', '.') }}<br>
                Tasse: € {{ number_format($totals['tax'] ?? 0, 2, ',', '.') }}</p>
                <p class="total">Totale: € {{ number_format($totals['total'], 2, ',', '.') }}</p>
            </div>

            <div class="section">
                <h3>Spedizione</h3>
                <p>
                    {{ $shipping['first_name'] }} {{ $shipping['last_name'] }}<br>
                    {{ $shipping['address_line_1'] }}<br>
                    @if(!empty($shipping['address_line_2'])) {{ $shipping['address_line_2'] }}<br>@endif
                    {{ $shipping['postal_code'] }} {{ $shipping['city'] }} ({{ $shipping['country'] }})
                </p>
            </div>

            <p>Riceverai aggiornamenti via email sul progresso dell'ordine (spedizione, consegna).</p>
        </div>
        <div class="footer">
            <p>CARDSWAP • Piattaforma di Scambio Carte</p>
            <p>Per assistenza: support@cardswap.com</p>
        </div>
    </div>
</body>
</html>


