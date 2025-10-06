<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nuovo Report - CARDSWAP</title>
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
        .field {
            margin-bottom: 15px;
        }
        .label {
            font-weight: bold;
            color: #1e40af;
        }
        .value {
            margin-top: 5px;
            padding: 8px;
            background-color: white;
            border-radius: 4px;
            border: 1px solid #d1d5db;
        }
        .details {
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚨 Nuovo Report - CARDSWAP</h1>
        <p>È stato segnalato un problema</p>
    </div>

    <div class="content">
        <div class="field">
            <div class="label">ID Prodotto:</div>
            <div class="value">{{ $product_id }}</div>
        </div>

        <div class="field">
            <div class="label">Nome Venditore:</div>
            <div class="value">{{ $seller_name }}</div>
        </div>

        <div class="field">
            <div class="label">Tipo di Problema:</div>
            <div class="value">{{ $problem_type }}</div>
        </div>

        <div class="field">
            <div class="label">Dettagli:</div>
            <div class="value details">{{ $details }}</div>
        </div>

        <div class="field">
            <div class="label">Email Segnalatore:</div>
            <div class="value">{{ $email }}</div>
        </div>

        <div class="field">
            <div class="label">Data e Ora:</div>
            <div class="value">{{ $timestamp }}</div>
        </div>

        <div class="field">
            <div class="label">IP Address:</div>
            <div class="value">{{ $ip_address }}</div>
        </div>
    </div>

    <div class="footer">
        <p>CARDSWAP - Sistema di Report Automatico</p>
        <p>Questo report è stato generato automaticamente dal sistema</p>
    </div>
</body>
</html>
