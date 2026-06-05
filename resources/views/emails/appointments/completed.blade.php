@component('mail::message')
# Appuntamento completato

Ciao {{ $cliente_nome }},

l'appuntamento per **{{ $animale_nome }}** è stato completato.

@component('mail::table')
| Dettaglio | |
|-----------|--------|
| Data | {{ $data }} |
| Ora | {{ $ora }} |
| Servizi | {{ $servizi }} |
@endcomponent

Grazie per averci scelto!

**{{ $salone_nome }}**
@endcomponent
