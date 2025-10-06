<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aggiornamento Verifica KYC - CARDSWAP</title>
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
            background: #1e40af;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background: #f8fafc;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .status-approved {
            background: #10b981;
            color: white;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
            margin: 20px 0;
        }
        .status-rejected {
            background: #ef4444;
            color: white;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
            margin: 20px 0;
        }
        .reason-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }
        .button {
            display: inline-block;
            background: #1e40af;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>CARDSWAP</h1>
        <p>Aggiornamento Verifica Identità</p>
    </div>

    <div class="content">
        <h2>Ciao {{ $user['name'] }},</h2>
        
        @if($status === 'approved')
            <div class="status-approved">
                <h3>✅ Verifica KYC Approvata!</h3>
                <p>La tua verifica dell'identità è stata approvata con successo.</p>
            </div>
            
            <p>Congratulazioni! Ora puoi utilizzare tutte le funzionalità della piattaforma CARDSWAP, inclusa la vendita di carte da collezione.</p>
            
            <p>Puoi ora:</p>
            <ul>
                <li>Creare inserzioni per le tue carte</li>
                <li>Ricevere pagamenti per le vendite</li>
                <li>Accedere a tutte le funzionalità venditore</li>
            </ul>
            
        @elseif($status === 'rejected')
            <div class="status-rejected">
                <h3>❌ Verifica KYC Rifiutata</h3>
                <p>La tua verifica dell'identità non è stata approvata.</p>
            </div>
            
            @if(isset($reason) && $reason)
                <div class="reason-box">
                    <h4>Motivo del rifiuto:</h4>
                    <p>{{ $reason }}</p>
                </div>
            @endif
            
            <p>Per favore, controlla i documenti caricati e assicurati che:</p>
            <ul>
                <li>Le immagini siano chiare e leggibili</li>
                <li>I documenti non siano scaduti</li>
                <li>Tutti i dati siano corretti e corrispondano al tuo profilo</li>
            </ul>
            
            <p>Puoi ricaricare i documenti corretti dal tuo profilo utente.</p>
        @endif

        @if(isset($notes) && $notes)
            <div class="reason-box">
                <h4>Note aggiuntive:</h4>
                <p>{{ $notes }}</p>
            </div>
        @endif

        <div style="text-align: center;">
            <a href="{{ config('app.url') }}/dashboard" class="button">Vai alla Dashboard</a>
        </div>

        <p>Se hai domande o hai bisogno di assistenza, non esitare a contattare il nostro team di supporto.</p>
    </div>

    <div class="footer">
        <p>CARDSWAP - La piattaforma per collezionisti di carte</p>
        <p>Questa email è stata inviata automaticamente, non rispondere a questo indirizzo.</p>
    </div>
</body>
</html>
