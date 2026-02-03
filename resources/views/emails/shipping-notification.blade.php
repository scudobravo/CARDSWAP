@extends('emails.base')

@section('content')
    <div class="greeting">Ciao {{ $user->name }},</div>
    <div class="message">
        @if(isset($order_number))
            <p><strong>Ordine #{{ $order_number }}</strong></p>
        @endif
        {!! nl2br(e($message)) !!}
    </div>
    @if(!empty($action_url))
        <p>
            <a href="{{ $action_url }}" class="button" target="_blank" rel="noopener">Vai al dettaglio</a>
        </p>
    @endif
    <div class="footer-text" style="margin-top: 24px; font-size: 12px; color: #6b7280;">
        Questa email è automatica. Non rispondere.
    </div>
@endsection
