<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Report Ricevuto - CARDSWAP</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #1e40af;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f8fafc;
            padding: 20px;
            border: 1px solid #e2e8f0;
        }
        .footer {
            background-color: #1e40af;
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 0 0 8px 8px;
            font-size: 12px;
        }
        .success-icon {
            text-align: center;
            font-size: 48px;
            margin-bottom: 20px;
        }
        .info-box {
            background-color: white;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #10b981;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>✅ Report Ricevuto</h1>
        <p>Grazie per la tua segnalazione</p>
    </div>

    <div class="content">
        <div class="success-icon">📧</div>
        
        <p>Ciao,</p>
        
        <p>Abbiamo ricevuto il tuo report riguardo al prodotto <strong>{{ $product_id }}</strong> del venditore <strong>{{ $seller_name }}</strong>.</p>
        
        <div class="info-box">
            <h3>Dettagli del Report:</h3>
            <p><strong>Tipo di Problema:</strong> {{ $problem_type }}</p>
            <p><strong>Data:</strong> {{ $timestamp }}</p>
            @if($details)
            <p><strong>Dettagli:</strong> {{ $details }}</p>
            @endif
        </div>
        
        <p>Il nostro team esaminerà la segnalazione e prenderà le azioni appropriate. Se necessario, ti contatteremo per ulteriori informazioni.</p>
        
        <p>Grazie per aver contribuito a mantenere CARDSWAP un ambiente sicuro e affidabile per tutti gli utenti.</p>
        
        <p>Saluti,<br>
        Il Team CARDSWAP</p>
    </div>

    <div class="footer">
        <p>CARDSWAP - Piattaforma di Scambio Carte</p>
        <p>Se hai domande, contattaci a support@cardswap.com</p>
    </div>
</body>
</html>
