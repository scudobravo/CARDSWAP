<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'CardSwap' }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            padding: 30px 20px;
            text-align: center;
        }
        .logo {
            max-width: 120px;
            height: auto;
        }
        .content {
            padding: 30px 20px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 20px;
        }
        .message {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: #ffffff;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
            transition: transform 0.2s ease;
        }
        .button:hover {
            transform: translateY(-1px);
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .footer-text {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 10px;
        }
        .footer-links {
            margin-top: 15px;
        }
        .footer-links a {
            color: #1e40af;
            text-decoration: none;
            margin: 0 10px;
            font-size: 14px;
        }
        .footer-links a:hover {
            text-decoration: underline;
        }
        .divider {
            height: 1px;
            background-color: #e5e7eb;
            margin: 20px 0;
        }
        .highlight {
            background-color: #fef3c7;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #f59e0b;
            margin: 20px 0;
        }
        .card-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
            border: 1px solid #e5e7eb;
        }
        .card-title {
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 10px;
        }
        .card-details {
            font-size: 14px;
            color: #6b7280;
        }
        .price {
            font-size: 18px;
            font-weight: 700;
            color: #059669;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <img src="{{ asset('images/logos/logo-blu.svg') }}" alt="CardSwap" class="logo">
        </div>

        <!-- Content -->
        <div class="content">
            @yield('content')
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-text">
                <strong>CardSwap</strong> - La piattaforma per il trading di carte sportive
            </div>
            <div class="footer-text">
                Ricevi questa email perché hai un account su CardSwap
            </div>
            <div class="footer-links">
                <a href="{{ url('/') }}">Visita il sito</a>
                <a href="{{ url('/dashboard') }}">Dashboard</a>
                <a href="{{ url('/support') }}">Supporto</a>
                <a href="{{ url('/privacy') }}">Privacy</a>
            </div>
            <div class="footer-text" style="margin-top: 15px; font-size: 12px;">
                © {{ date('Y') }} CardSwap. Tutti i diritti riservati.
            </div>
        </div>
    </div>
</body>
</html>
