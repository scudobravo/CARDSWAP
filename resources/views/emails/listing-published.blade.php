@extends('emails.base')

@section('content')
    <div class="greeting">
        Ciao {{ $user->first_name }}! 🎉
    </div>

    <div class="message">
        La tua inserzione per la carta <strong>"{{ $listing->title }}"</strong> è stata pubblicata con successo e ora è visibile a tutti gli utenti della piattaforma.
    </div>

    <div class="card-info">
        <div class="card-title">{{ $listing->title }}</div>
        <div class="card-details">
            <strong>Condizione:</strong> {{ ucfirst(str_replace('_', ' ', $listing->condition)) }}<br>
            <strong>Quantità:</strong> {{ $listing->quantity }}<br>
            <strong>Prezzo:</strong> <span class="price">€{{ number_format($listing->price, 2) }}</span><br>
            @if($listing->is_foil)
                <strong>Foil:</strong> Sì<br>
            @endif
            @if($listing->is_signed)
                <strong>Firmata:</strong> Sì<br>
            @endif
            @if($listing->is_first_edition)
                <strong>Prima Edizione:</strong> Sì<br>
            @endif
        </div>
    </div>

    <div class="message">
        La tua inserzione è ora attiva e gli utenti possono visualizzarla e acquistarla. Riceverai una notifica ogni volta che qualcuno acquista una delle tue carte.
    </div>

    <div style="text-align: center;">
        <a href="{{ url('/listings/' . $listing->id) }}" class="button">
            Visualizza Inserzione
        </a>
    </div>

    <div class="divider"></div>

    <div class="message">
        <strong>Suggerimenti per massimizzare le vendite:</strong>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>Mantieni il prezzo competitivo rispetto al mercato</li>
            <li>Rispondi rapidamente ai messaggi degli acquirenti</li>
            <li>Aggiorna regolarmente le tue inserzioni</li>
        </ul>
    </div>
@endsection
