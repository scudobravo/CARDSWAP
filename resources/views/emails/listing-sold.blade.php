@extends('emails.base')

@section('content')
    <div class="greeting">
        Ciao {{ $user->first_name }}! 🎊
    </div>

    <div class="message">
        Ottima notizia! La tua inserzione per la carta <strong>"{{ $listing->title }}"</strong> è stata venduta.
    </div>

    <div class="card-info">
        <div class="card-title">{{ $listing->title }}</div>
        <div class="card-details">
            <strong>Condizione:</strong> {{ ucfirst(str_replace('_', ' ', $listing->condition)) }}<br>
            <strong>Quantità venduta:</strong> {{ $order->orderItems->where('card_listing_id', $listing->id)->first()->quantity ?? 1 }}<br>
            <strong>Prezzo unitario:</strong> <span class="price">€{{ number_format($listing->price, 2) }}</span><br>
            <strong>Totale ordine:</strong> <span class="price">€{{ number_format($order->total_amount, 2) }}</span><br>
            <strong>Numero ordine:</strong> #{{ $order->order_number }}
        </div>
    </div>

    <div class="highlight">
        <strong>💰 Pagamento:</strong> Riceverai il pagamento a breve. Il denaro verrà accreditato sul tuo account Stripe entro 2-7 giorni lavorativi.
    </div>

    <div class="message">
        <strong>Prossimi passi:</strong>
        <ol style="margin: 10px 0; padding-left: 20px;">
            <li>Prepara la carta per la spedizione</li>
            <li>Usa un imballaggio protettivo</li>
            <li>Spedisci entro 2 giorni lavorativi</li>
            <li>Aggiorna lo stato dell'ordine con il codice di tracciamento</li>
        </ol>
    </div>

    <div style="text-align: center;">
        <a href="{{ url('/orders/' . $order->id) }}" class="button">
            Visualizza Ordine
        </a>
    </div>

    <div class="divider"></div>

    <div class="message">
        <strong>💡 Suggerimento:</strong> Continua a pubblicare inserzioni per aumentare le tue vendite. Gli utenti amano vedere una selezione varia di carte!
    </div>
@endsection
