@component('mail::message')
# Promemoria appuntamento

Ciao {{ $cliente_nome }},

ti ricordiamo l'appuntamento per **{{ $animale_nome }}** di {{ $quando }}.

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
