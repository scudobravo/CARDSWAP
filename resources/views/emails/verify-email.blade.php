@extends('emails.base', ['subject' => 'Conferma il tuo account'])

@section('content')
    <div class="greeting">Ciao {{ $user->name }},</div>
    <div class="message">
        Grazie per esserti registrato su CardSwap. Per attivare il tuo account, conferma il tuo indirizzo email cliccando sul pulsante qui sotto.
    </div>
    <div style="text-align: center;">
        <a href="{{ $verifyUrl }}" class="button">Conferma email</a>
    </div>
    <div class="message" style="font-size: 14px; color: #6b7280;">
        Se non ti sei registrato su CardSwap, puoi ignorare questa email.
    </div>
@endsection
