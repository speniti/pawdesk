@component('mail::message')
# Nuova richiesta di appuntamento

Ciao {{ $cliente_nome }},

abbiamo ricevuto la tua richiesta di appuntamento per **{{ $animale_nome }}**.

@component('mail::table')
| Dettaglio | |
|-----------|--------|
| Data | {{ $data }} |
| Ora | {{ $ora }} |
| Servizi | {{ $servizi }} |
@endcomponent

Ti contatteremo al più presto per la conferma.

Grazie,
**{{ $salone_nome }}**
@endcomponent
