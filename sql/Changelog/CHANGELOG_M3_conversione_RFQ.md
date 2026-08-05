# Allonwheel - M3: Conversione RFQ e segnali di fiducia - 2026-07-06

Terzo blocco del piano v1.1. Un solo ZIP. CRLF. PHP 8.3 lint OK.
ORDINE: eseguire PRIMA la patch SQL, poi caricare i file.

## 1. CTA "Request a quotation" SOPRA LA PIEGA (shared/view_ad.php)
La scheda annuncio non aveva NESSUNA CTA verso la RFQ (solo la voce nel menu).
Ora, subito sotto il titolo: pulsante "Request a quotation" + riga di fiducia
con SOLO dati reali (dir. 14):
- "N verified suppliers in this family will receive your request" (N calcolato
  dal ponte macro->fornitori, lo stesso usato dall'invio; se N=0 o famiglia
  assente: copy neutro "Your request reaches specialist suppliers...");
- "Free - No obligation - Typical reply within a few business days" (copy
  neutro finche' non esiste una baseline reale dei tempi di risposta, come da
  piano; quando M5 avra' dati, si sostituira' col valore calcolato).
La CTA porta la famiglia dell'annuncio: `04_request_offer.php?macro=<slug>`.

## 2. Prefill famiglia nel form RFQ (04_request_offer.php)
`?macro=` validato con whitelist (`ProductMacro::exists`) -> campo hidden
`macro` nel form -> `04_send_offer.php` (che gia' legge `$_POST['macro']`)
instrada il lead ai fornitori GIUSTI della famiglia. Prima, arrivando dal menu,
la famiglia restava vuota.

## 3. Rate-limit anti-abuso (04_send_offer.php)
Max **5 richieste/ora per IP** (stesso hash SHA-256 salato gia' usato per il
consenso GDPR: nessun IP in chiaro). Superata la soglia -> redirect alla retry
page. Fail-open: se il DB non risponde, non blocca l'utente legittimo.
Protegge i fornitori dallo spam (rimedio diretto del piano M3).

## 4. Attribuzione del lead (SQL + insert)
Nuova colonna `quote_requests.source_page` (patch run-once) popolata con il
referer (troncato a 255): la dashboard KPI di M5 potra' dire DA QUALE pagina
nascono le RFQ (scheda annuncio vs menu vs campagne con UTM).

## 5. Pagina di successo con i prossimi passi (04_contact-success.php)
Al posto della riga generica: "Request sent!" + **What happens next** in 3
passi (inoltrata ai fornitori della famiglia / ti rispondono via email in
pochi giorni lavorativi / confronta le offerte, nessun obbligo) + CTA
"Keep browsing the marketplace". Il link al PDF tecnico resta.

## File (4 PHP + 1 SQL)
shared/view_ad.php | 04_request_offer/{04_request_offer,04_send_offer,
04_contact-success}.php | sql/Changelog/2026-07-06_rfq_source_page.sql

## Test rapidi
1. Apri una scheda annuncio: CTA + riga fiducia subito sotto il titolo.
2. Clicca la CTA: il form RFQ arriva; invia: il lead in _admin/leads.php ha la
   famiglia giusta e (dopo la patch SQL) la source_page.
3. Invia 6 RFQ di fila dallo stesso IP: la sesta finisce sulla retry page.
4. Success page: 3 passi + pulsante marketplace.

Prossimo blocco al tuo "procedi": **M4 - Email lifecycle** (ricerca salvata
con alert, digest settimanale; l'SMTP autenticato resta task tuo lato DNS).
