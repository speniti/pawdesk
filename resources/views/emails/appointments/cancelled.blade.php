@component('mail::message')
# Appuntamento cancellato

Ciao {{ $cliente_nome }},

l'appuntamento per **{{ $animale_nome }}** programmato per il {{ $data }} alle {{ $ora }} è stato cancellato.

Per qualsiasi domanda, non esitare a contattarci.

**{{ $salone_nome }}**
@endcomponent
