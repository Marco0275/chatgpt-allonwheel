# Allonwheel — Faccette tecniche: analisi e raccomandazione
17 lug 2026. Punto 4 residuo. Questa è un'analisi, non codice: il codice
che serviva sarebbe stato inutile, e spiego perché.

## La domanda che avevi lasciato aperta
"Faccette tecniche (da 03_ads_tech_details): specifiche base anche al free?"
Mi avevi delegato la decisione. Prima di scrivere, ho guardato i dati reali.

## Cosa ho trovato in 03_ads_tech_details
- **52 campi**, quasi tutti booleani molto specifici: Awning, Workshop,
  Belly, Kitchen, Beds, Genset, Bathroom, SAT, LED, rails, Cabinets...
  Sono feature da race-trailer/hospitality di fascia alta, non attributi
  universali di un veicolo commerciale.
- Le uniche misure "dimensionali" (ext_length, ext_width, ext_height, axles,
  Lift_*) sono **varchar**, cioè stringhe libere: un venditore scrive "12.5",
  un altro "12,5 m", un altro "12500mm". Non sono numeri: non ci si può
  filtrare sopra in modo affidabile (>= 10m non funziona su "12,5 m").
- È tutto legato alla **premium**: i free non hanno questi campi.

## Perché NON ho costruito la faccetta tecnica
Costruirla oggi darebbe uno di questi due esiti, entrambi negativi:

1. **Se la limito alla premium**: la faccetta "lunghezza >= 10m" mostrerebbe
   SOLO annunci premium e farebbe sparire tutti i free. Su un marketplace
   pre-lancio, dove i free saranno la maggioranza, è un autogol: il
   compratore filtra e "spariscono" gli annunci.

2. **Se aggiungo i campi al free**: dovrei infilare decine di campi tecnici
   nel wizard free — quello che si è già rotto in produzione una volta — che
   il 90% dei venditori free non compilerebbe. Faccette su campi vuoti =
   filtri che non danno risultati = sito che sembra rotto (l'opposto di
   dir. 14: solo dati reali).

In più le misure sono varchar: anche volendo, "filtro per lunghezza" non
funziona finché i dati non sono numeri veri.

Costruire una faccetta sapendo che darà risultati vuoti o incoerenti sarebbe
peggio che non costruirla. La faccetta che funziona su TUTTI gli annunci —
condizione + fascia di prezzo — l'ho già messa in sidebar la scorsa volta.

## La strada giusta, quando avrà senso (in ordine)
Se e quando vorrai il filtro dimensionale, il percorso onesto è:

1. **Normalizzare le misure**: aggiungere agli annunci (02 e 03) tre colonne
   numeriche vere — `length_cm`, `width_cm`, `height_cm` (INT, in cm, così
   niente virgole/unità ambigue) — e un `axles` numerico. Migrazione dai
   varchar esistenti con parsing tollerante, dove possibile; i non
   interpretabili restano NULL (niente dati inventati, dir. 14).
2. **Chiederle nel wizard** come campi facoltativi ma NUMERICI, con unità
   fissa nell'etichetta ("Length (m)"), sia free che premium: sono le 3-4
   misure che un compratore B2B cerca davvero, non le 52 feature.
3. **Solo allora** una faccetta "Length / Axles" in sidebar, che filtra su
   colonne numeriche e mostra risultati reali.

È un blocco che tocca schema (dir. 9), wizard (il file fragile) e migrazione
dati: va deciso e fatto con calma, non incastrato ora. Ma è ANCHE il punto 7
del tuo piano originale ("Configuratore Step 2 tecnico"): quando lo
affronterai, la normalizzazione delle misure è il primo mattone, e questa
faccetta ne è la conseguenza gratuita.

## Cosa NON serve fare
Aggiungere le 52 feature booleane come faccette. Nessun compratore filtra un
veicolo per "ha lo SAT sì/no": quelle stanno bene dove sono, nella scheda
tecnica dell'annuncio premium come dettaglio, non come filtro.

## Riepilogo decisione
- Faccetta tecnica generica: **non fatta, di proposito** (darebbe risultati
  vuoti/incoerenti oggi).
- Faccetta reale che serve: **condizione + prezzo, già in sidebar**.
- Filtro dimensionale: **rimandato al configuratore tecnico** (punto 7),
  perché richiede prima di normalizzare le misure da varchar a numeri —
  una tua decisione su schema + wizard + migrazione.
