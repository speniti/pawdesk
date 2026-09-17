<div class="space-y-6 text-base leading-7 dark:text-gray-200">
    <section class="space-y-2">
        <h3 class="text-lg font-semibold">1. Titolare del trattamento</h3>
        <p>
            Il titolare del trattamento è
            <strong>{{ $tenant->privacyBusinessName() }}</strong>@if($tenant->privacyOwnerName()) ,
            in persona del legale rappresentativo <strong>{{ $tenant->privacyOwnerName() }}</strong>@endif .
        </p>
        @if($tenant->privacyBusinessAddress())
            <p>Sede legale: {{ $tenant->privacyBusinessAddress() }}.</p>
        @endif
        @if($tenant->privacyVatNumber())
            <p>Partita IVA: {{ $tenant->privacyVatNumber() }}.</p>
        @endif
        @if($tenant->privacyContactEmail() || $tenant->privacyContactPhone())
            <p>
                Contatti:@if($tenant->privacyContactEmail()) email {{ $tenant->privacyContactEmail() }}@endif
                @if($tenant->privacyContactEmail() && $tenant->privacyContactPhone()) — @endif
                @if($tenant->privacyContactPhone()) telefono {{ $tenant->privacyContactPhone() }}@endif.
            </p>
        @endif
    </section>

    <section class="space-y-2">
        <h3 class="text-lg font-semibold">2. Dati personali trattati</h3>
        <p>
            Trattiamo i seguenti dati personali:
        </p>
        <ul class="list-disc pl-5 space-y-1">
            <li><strong>dati identificativi e di contatto</strong> del cliente (nome, cognome, indirizzo, numero di telefono, indirizzo email);</li>
            <li><strong>dati dell'animale</strong> (nome, specie, taglia, caratteristiche del manto, note cure e comportamento), necessari per la prestazione di tolettatura;</li>
            <li><strong>dati relativi agli appuntamenti</strong> e ai trattamenti effettuati;</li>
            <li><strong>dati di pagamento</strong> relativi alle prestazioni rese.</li>
        </ul>
        <p>
            Non trattiamo dati sanitari o categorie particolari di dati personali. Eventuali note sulle
            caratteristiche dell'animale sono trattate esclusivamente per garantire la sicurezza
            dell'animale e dell'operatore durante la tolettatura.
        </p>
    </section>

    <section class="space-y-2">
        <h3 class="text-lg font-semibold">3. Finalità e basi giuridiche del trattamento</h3>
        <ul class="list-disc pl-5 space-y-1">
            <li>
                <strong>gestione degli appuntamenti e delle prestazioni di tolettatura</strong>
                (prenotazione, promemoria, conferma, esecuzione della prestazione): base giuridica è
                l'esecuzione del contratto di cui sei parte, art. 6 co. 1 lett. b) GDPR;
            </li>
            <li>
                <strong>adempiere a obblighi legali, contabili e fiscali</strong> (emissione ricevute,
                conservazione dei documenti): base giuridica è l'obbligo legale, art. 6 co. 1 lett. c) GDPR;
            </li>
            <li>
                <strong>sicurezza dell'animale e contatto in caso di urgenze</strong> (ad esempio il
                richiamo del proprietario durante la prestazione): base giuridica è il legittimo
                interesse, art. 6 co. 1 lett. f) GDPR;
            </li>
            <li>
                <strong>invio di comunicazioni promozionali e offerte</strong> relative ai servizi del
                salone: base giuridica è il consenso, art. 6 co. 1 lett. a) GDPR, revocabile in
                qualsiasi momento.
            </li>
        </ul>
    </section>

    <section class="space-y-2">
        <h3 class="text-lg font-semibold">4. Natura del conferimento</h3>
        <p>
            Il conferimento dei dati indicati alle finalità di cui ai punti 1, 2 e 3 (appuntamenti,
            obblighi legali, sicurezza) è necessario per la fornitura del servizio di tolettatura: in
            assenza non è possibile gestire la prenotazione né eseguire la prestazione. Il conferimento
            dei dati per finalità di marketing è facoltativo e il relativo rifiuto non ha alcuna
            conseguenza sull'erogazione del servizio.
        </p>
    </section>

    <section class="space-y-2">
        <h3 class="text-lg font-semibold">5. Modalità del trattamento e conservazione</h3>
        <p>
            I dati sono trattati con strumenti elettronici e, per quanto necessario, cartacei, secondo
            i principi di correttezza, liceità e trasparenza e di minimizzazione dei dati, adottando
            misure di sicurezza adeguate a proteggerli. I dati sono conservati per il tempo necessario
            alle finalità per cui sono raccolti e, in particolare:
        </p>
        <ul class="list-disc pl-5 space-y-1">
            <li>dati contrattuali e di contatto: per la durata del rapporto e per i successivi 10 (dieci) anni, in adempimento agli obblighi fiscali e amministrativi;</li>
            <li>dati trattati sulla base del consenso per il marketing: fino alla revoca del consenso;</li>
            <li>log delle comunicazioni di servizio: per il tempo necessario a garantire la corretta erogazione del servizio.</li>
        </ul>
    </section>

    <section class="space-y-2">
        <h3 class="text-lg font-semibold">6. Destinatari e trasferimenti dei dati</h3>
        <p>
            I dati non vengono diffusi né ceduti a terzi per finalità proprie. Possono essere trattati
            da soggetti che forniscono servizi strumentali alle finalità sopra indicate, nominati
            responsabili del trattamento ex art. 28 GDPR, tra cui il fornitore della piattaforma di
            gestione degli appuntamenti e i fornitori dei servizi di invio di email e SMS. L'eventuale
            trasferimento di dati verso paesi extra-UE avviene esclusivamente verso paesi che garantiscono
            adeguate garanzie o in presenza di adeguate tutele ai sensi degli artt. 44-49 GDPR.
        </p>
    </section>

    <section class="space-y-2">
        <h3 class="text-lg font-semibold">7. Diritti dell'interessato</h3>
        <p>
            Hai il diritto di ottenere, in relazione ai tuoi dati personali: l'accesso (art. 15), la
            rettifica (art. 16), la cancellazione (art. 17), la limitazione del trattamento (art. 18),
            la portabilità dei dati (art. 20) e l'opposizione al trattamento (art. 21). Hai inoltre il
            diritto di revocare in qualsiasi momento il consenso, senza tuttavia pregiudicare la
            liceità del trattamento basato sul consenso prestato prima della revoca (art. 13 co. 2
            lett. c) e art. 7 co. 3 GDPR).
        </p>
        <p>
            Per esercitare i tuoi diritti puoi scrivere a
            @if($tenant->privacyContactEmail())
                <strong>{{ $tenant->privacyContactEmail() }}</strong>
            @else
                al contatto del titolare sopra indicato
            @endif
            .
        </p>
        <p>
            Hai inoltre il diritto di proporre reclamo al Garante per la protezione dei dati personali
            (<a class="underline" href="https://www.garanteprivacy.it" rel="noopener noreferrer">www.garanteprivacy.it</a>),
            art. 77 GDPR.
        </p>
    </section>

    <section class="space-y-2">
        <h3 class="text-lg font-semibold">8. Aggiornamento dell'informativa</h3>
        <p>
            La presente informativa può subire modifiche nel tempo. Ti invitiamo a consultare
            periodicamente questa pagina.
        </p>
    </section>
</div>
