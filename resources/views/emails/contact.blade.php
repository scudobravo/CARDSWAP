<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nuovo messaggio da Contattaci - CardSwap</title>
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
        .message-body {
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Nuovo messaggio - Contattaci</h1>
        <p>Un visitatore ha inviato un messaggio dal modulo Contattaci</p>
    </div>

    <div class="content">
        <div class="field">
            <div class="label">Oggetto:</div>
            <div class="value">{{ $subject_display ?? $subject ?? '—' }}</div>
        </div>

        <div class="field">
            <div class="label">Nome e cognome:</div>
            <div class="value">{{ $first_name }} {{ $last_name }}</div>
        </div>

        <div class="field">
            <div class="label">Email:</div>
            <div class="value"><a href="mailto:{{ $email }}">{{ $email }}</a></div>
        </div>

        @if(!empty($phone))
        <div class="field">
            <div class="label">Telefono:</div>
            <div class="value">{{ $phone }} @if(!empty($country))({{ $country }})@endif</div>
        </div>
        @endif

        <div class="field">
            <div class="label">Messaggio:</div>
            <div class="value message-body">{{ $message }}</div>
        </div>

        <div class="field">
            <div class="label">Data e ora:</div>
            <div class="value">{{ $submitted_at }}</div>
        </div>
    </div>

    <div class="footer">
        <p>CardSwap - Modulo Contattaci</p>
        <p>Rispondi a: {{ $email }}</p>
    </div>
</body>
</html>
