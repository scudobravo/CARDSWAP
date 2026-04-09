@extends('emails.base')

@section('content')
    @php
        $isRookieCard = (bool) optional($listing->cardModel)->is_rookie;
        $emailTitle = $listing->title;

        if ($isRookieCard) {
            if (str_contains($emailTitle, '(Prima Edizione)')) {
                $emailTitle = str_replace('(Prima Edizione)', '(Rookie)', $emailTitle);
            } elseif (!str_contains($emailTitle, '(Rookie)')) {
                $emailTitle .= ' (Rookie)';
            }
        }
    @endphp

    <div class="greeting">
        Ciao {{ $user->first_name }}! 🔔
    </div>

    <div class="message">
        Ottima notizia! La carta <strong>"{{ $emailTitle }}"</strong> che hai nella tua wishlist è ora disponibile per l'acquisto.
    </div>

    <div class="card-info">
        <div class="card-title">{{ $emailTitle }}</div>
        <div class="card-details">
            <strong>Condizione:</strong> {{ ucfirst(str_replace('_', ' ', $listing->condition)) }}<br>
            <strong>Quantità disponibile:</strong> {{ $listing->quantity }}<br>
            <strong>Prezzo:</strong> <span class="price">€{{ number_format($listing->price, 2) }}</span><br>
            @if($listing->is_foil)
                <strong>Foil:</strong> Sì<br>
            @endif
            @if($listing->is_signed)
                <strong>Firmata:</strong> Sì<br>
            @endif
            @if($listing->is_first_edition)
                <strong>{{ $isRookieCard ? 'Rookie' : 'Prima Edizione' }}:</strong> Sì<br>
            @endif
            @if($listing->is_negotiable)
                <strong>Prezzo negoziabile:</strong> Sì<br>
            @endif
        </div>
    </div>

    <div class="highlight">
        <strong>⚡ Attenzione:</strong> Questa carta potrebbe essere molto richiesta! Non perdere l'occasione di aggiungerla alla tua collezione.
    </div>

    <div style="text-align: center;">
        <a href="{{ url('/listings/' . $listing->id) }}" class="button">
            Visualizza e Acquista
        </a>
    </div>

    <div class="divider"></div>

    <div class="message">
        <strong>💡 Suggerimenti:</strong>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>Controlla le foto per verificare le condizioni</li>
            <li>Leggi la descrizione del venditore</li>
            <li>Se il prezzo è negoziabile, prova a fare un'offerta</li>
            <li>Verifica la reputazione del venditore</li>
        </ul>
    </div>

    <div class="message">
        <strong>🔔 Notifiche:</strong> Ricevi questa email perché hai aggiunto questa carta alla tua wishlist. Puoi disattivare queste notifiche nelle impostazioni del tuo account.
    </div>
@endsection
