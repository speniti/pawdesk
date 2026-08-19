@component('mail::message')
# Promemoria appuntamento

Ciao {{ $cliente_nome }},

ti ricordiamo l'appuntamento per **{{ $animale_nome }}** @if($ore >= 12) di domani @else di oggi @endif.

@component('mail::table')
| Dettaglio | |
|-----------|--------|
| Data | {{ $data }} |
| Ora | {{ $ora }} |
| Servizi | {{ $servizi }} |
@endcomponent

Ti aspettiamo!

**{{ $salone_nome }}**
@endcomponent
