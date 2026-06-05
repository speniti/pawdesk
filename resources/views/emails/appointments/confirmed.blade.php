@component('mail::message')
# Appuntamento confermato

Ciao {{ $cliente_nome }},

il tuo appuntamento per **{{ $animale_nome }}** è stato confermato.

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
