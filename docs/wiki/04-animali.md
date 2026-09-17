# Animali

Anagrafica degli animali del salone. Nel menu l'area si chiama **Pets**; nel manuale si parla di animale.

Ogni animale è collegato a un cliente (proprietario). La **taglia** dell'animale determina il prezzo applicato ai servizi che prevedono prezzi per taglia (vedi [Servizi](./05-servizi.md)).

## Creare un animale

1. Da **Pets** premi **Nuovo pet**, oppure usa **Nuovo pet** nella scheda *Pets* della vista cliente.
2. Sezione **Dati anagrafici**:
   - **Proprietario** — obbligatorio, ricerca per nome;
   - **Nome** — obbligatorio;
   - **Data di nascita** — facoltativa, non può essere nel futuro.
3. Sezione **Caratteristiche fisiche**:
   - **Specie** — obbligatoria: Cane, Gatto, Altro;
   - **Razza** — testo libero;
   - **Sesso** — Maschio, Femmina, Sconosciuto (predefinito);
   - **Taglia** — obbligatoria: Toy, Piccolo, Medio, Grande, Gigante;
   - **Manto** — facoltativo: Corto, Pelo corto, Liscio, Piatto, Lungo, Piumato, Riccio, Spaniel, Doppio pelo, Primitivo.
4. **Note comportamentali** e **Note sanitarie** (fino a 2000 caratteri ciascuna): reazioni, morsichi, allergie, condizioni mediche. Sono il primo riferimento per lavorare in sicurezza.
5. Premi **Crea**.

## La tabella

Colonne: nome, specie, razza, taglia, proprietario. Filtri: **Specie**, **Taglia**, **Manto**. Azioni **Visualizza** e **Modifica** nel menu ⋮.

## Vista animale

Statistiche in alto: **Spesa totale** e numero di **Appuntamenti**.

- **Dati anagrafici** — data di nascita e link al proprietario.
- **Caratteristiche fisiche** — specie, razza, sesso, taglia, manto.
- **Note comportamentali** e **Note sanitarie**.
- Scheda **Appuntamenti** — storico in sola lettura (inizio, stato, fine, note interne). Gli appuntamenti si creano e modificano solo dal [calendario](./02-calendario-e-appuntamenti.md).
- Scheda **Trattamenti** — trattamenti collegati all'animale.
