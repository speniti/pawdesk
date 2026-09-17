@component('mail::message')
# Informativa sulla privacy

Ciao {{ $cliente_nome }},

di seguito trovi l'informativa sul trattamento dei tuoi dati personali e di quelli del tuo animale, rilasciata da **{{ $salone_nome }}**.

@component('mail::button', ['url' => $url])
Leggi l'informativa
@endcomponent

Se il pulsante non funziona, copia e incolla questo link nel browser:

{{ $url }}

**{{ $salone_nome }}**
@endcomponent
