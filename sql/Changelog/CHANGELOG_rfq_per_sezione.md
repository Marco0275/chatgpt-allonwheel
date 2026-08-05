# Allonwheel — RFQ divisa per sezione (l'ultimo punto in sospeso)
23 lug 2026. UN SOLO ZIP. CRLF. PHP lint 289/289 OK.
Base: Allonwheel_3_3_variabili.

=================================================================
PRIMA HO CONTROLLATO COSA MANCAVA DAVVERO
=================================================================
Ho lavorato su basi diverse (V3.1 -> V3.2 -> V3.3) e alcuni pacchetti
potevano non essere stati riportati. Verificato uno per uno sulla V3.3:

  notifyBuyers alla pubblicazione ..... GIA' DENTRO
  moderazione (config/app_settings) ... GIA' DENTRO
  check_schema.php .................... GIA' DENTRO
  rfq_escalation.php .................. GIA' DENTRO
  rfq_claim_reassign.php .............. GIA' DENTRO
  area lead fornitori (06_40) ......... GIA' DENTRO
  bozze wizard ospite (ad_drafts) ..... GIA' DENTRO
  modifica per sezione (02/03_modify_*) GIA' DENTRO
  RFQ per sezione ..................... MANCAVA  <-- questo pacchetto

Quindi restava solo la RFQ. Il resto del lavoro e' tutto in produzione.

=================================================================
COSA FA
=================================================================
La richiesta di preventivo era UN form con tutte le categorie mescolate:
veicoli stradali, mezzi da paddock e shelter nello stesso elenco. Ora ogni
sezione ha la sua RFQ, con SOLO le categorie pertinenti.

  ?section=road     24 categorie stradali (ambulanze, cassoni, frigoriferi,
                    minibus, scuolabus...). La tabella "Special categories"
                    non compare affatto.
  ?section=special  8 categorie: le "non stradali" (camper, laboratori medici
                    mobili, uffici mobili) piu' racing trailer, box trailer,
                    hospitality, paddock trailer, motorhome.
  ?section=shelter  solo Shelter & Container. La tabella "Vehicle body types"
                    non compare.
  nessuna section   tutto, come prima -> i link generici (header, footer,
                    home) continuano a funzionare senza modifiche.
  section ignota    ricade sul comportamento completo, non da errore.

Verificato a codice: le 27 categorie regular e le 6 special stanno in
ESATTAMENTE una sezione. Nessuna orfana, nessuna doppia.

=================================================================
COME CI SI ARRIVA
=================================================================
Nuovo box in sidebar (sidebar_rfq_cta.php): sulle pagine Road, Special e
Shelter compare "Request a quotation" che porta alla RFQ di QUELLA sezione.
Sulle altre pagine il box non compare, perche' li' la RFQ giusta e' quella
completa.

Il box usa solo classi esistenti (.sb_box, .more), zero stile inline (dir. 8),
e ha una guardia sull'i18n: se la pagina non avesse caricato le traduzioni si
carica da se' invece di interrompere la sidebar.

=================================================================
TRACCIABILITA'
=================================================================
04_send_offer.php registra la sezione dentro categories_json del lead
(validata contro le sezioni note): si sa da dove nasce ogni richiesta, senza
toccare lo schema del database.

=================================================================
FILE IN QUESTO ZIP (12)
=================================================================
NUOVO
  sidebar_rfq_cta.php                    box CTA di sezione
MODIFICATI
  libs/06_company.class.php              $rfqSections + rfqCategoriesFor()
  04_request_offer/04_request_offer.php  legge ?section, filtra, titolo
  04_request_offer/04_send_offer.php     sezione nel lead
  include_sidebar.php                    include il box CTA
  road_vehicles.php                      dichiara sezione 'road'
  special_vehicles.php                   dichiara sezione 'special'
  shelter_container.php                  dichiara sezione 'shelter'
  lang/en.php it.php fr.php de.php       3 chiavi rfq.cta_* per lingua

## Come verificare
1. /04_request_offer/04_request_offer.php?section=road -> solo le 24
   categorie stradali, nessuna tabella "Special categories".
2. ?section=shelter -> una sola voce, nessuna tabella "Vehicle body types".
3. Senza ?section -> elenco completo, come prima.
4. Apri Road/Special/Shelter: in sidebar c'e' il box "Request a quotation"
   che porta alla RFQ di quella sezione. Su browse o home non compare.

=================================================================
COSA RESTA APERTO (non e' codice che posso scrivere da solo)
=================================================================
- Premium tech_details nella bozza dell'ospite: oggi la bozza salva i campi
  base; se l'ospite deve compilare anche la scheda tecnica prima di
  registrarsi e' un'aggiunta al payload della bozza. E' una scelta di
  prodotto: mi dici se la vuoi e la faccio.
- Task infra tuoi, che avevi escluso: SMTP con SPF/DKIM, HISTATS_ID reale,
  URL social nel footer.
