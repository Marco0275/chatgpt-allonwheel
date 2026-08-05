# Allonwheel — Misure dimensionali + faccetta (#2 dei tre punti)
20 lug 2026. Un solo ZIP. CRLF. PHP lint 720/720 OK. SQL 5.7.

===========================================================
PERCHE' ORA E' SICURO (era il punto che temevo)
===========================================================
Nella mia analisi precedente le faccette tecniche erano "da non fare": le
misure stavano in 03_ads_tech_details come VARCHAR (non filtrabili), solo
premium, e temevo una migrazione rischiosa. Verificando il DB reale ho
scoperto che quella tabella e' VUOTA: nessun dato storico da migrare. Quindi
si possono introdurre misure NUMERICHE pulite dal primo giorno, senza rischio
di corrompere nulla. Il blocco grande e rischioso si e' rivelato fattibile.

===========================================================
COSA HO FATTO (catena completa, end-to-end)
===========================================================
1. SCHEMA (2026-07-20_dimensions.sql): 4 colonne numeriche su ENTRAMBE le
   tabelle annunci (02_free_ads e 03_ads, cosi' free e premium sono filtrabili
   uguale):
     length_cm, width_cm, height_cm  (SMALLINT UNSIGNED, in centimetri)
     axles_n                          (TINYINT UNSIGNED)
   Unita' FISSA: niente "12,5 m" vs "12500mm". NULL = non specificato (dir.14).
   + indici su length_cm per la faccetta. Idempotente, non distruttiva: NON
   tocca ext_length &c. (restano come dettaglio testuale del premium).

2. WIZARD (02_insert_ad.php): 4 campi numerici OPZIONALI dopo il prezzo,
   unita' nell'etichetta ("Length (cm)", "Axles"). Stesso pattern dei campi
   esistenti (form_row, input_field, prefill $aow_dv). Free e premium.

3. HANDLER (02_01_upload_advertising.php): legge, VALIDA (vuoto o non-numerico
   -> NULL; cap ai limiti colonna) e salva le 4 misure. Innesto ADDITIVO sul
   file fragile: colonne + placeholder + binding aggiunti in modo simmetrico.
   VERIFICATO il bilanciamento: 31 colonne = 31 valori (30 placeholder +
   DATE_ADD), 30 placeholder = 30 binding, corrispondenza perfetta. L'INSERT
   e il cuore del wizard restano intatti.

4. BOZZA OSPITE (02_save_draft.php): le 4 misure entrano nel payload della
   bozza, cosi' un ospite che le compila e poi si registra NON le perde
   (chiude il cerchio col punto 2). Il prefill nel form gia' le rilegge.

5. FACCETTA (sidebar_facets.php): filtro "Min/Max length" in METRI (piu'
   naturale dell'input in cm). Stesso stile di pmin/pmax. Solo classi
   esistenti (dir. 8), filtro SOLO in sidebar (dir. 21).

6. BROWSE (browse.php): applica il filtro lunghezza (m -> cm) a ENTRAMBI i
   rami dell'UNION free+premium. VERIFICATO il bilanciamento dei bind:
   array_merge($bind,$bind) duplica correttamente i parametri per i due rami.

7. DETTAGLIO (shared/view_ad.php): mostra le misure (in metri) solo se
   valorizzate, dopo il prezzo. Cosi' l'utente le vede, non solo le inserisce.

8. i18n: facet.len_min / facet.len_max tradotte in EN/IT/FR/DE.

===========================================================
COSA NON HO FATTO (di proposito)
===========================================================
Le 52 feature booleane di tech_details (Awning, Genset, SAT...) NON diventano
faccette: nessuno filtra un veicolo per "ha lo SAT si/no". Restano dove sono,
come dettaglio della scheda premium. La faccetta dimensionale (lunghezza) e'
quella che un compratore B2B usa davvero.
Ho aggiunto la sola LUNGHEZZA come filtro (la piu' cercata); width/height/axles
sono salvati e mostrati, e si aggiungono alla faccetta in un attimo se li vuoi
- ma partire con un filtro solo tiene la sidebar pulita.

===========================================================
FILE IN QUESTO ZIP (11)
===========================================================
NUOVO
  sql/Changelog/2026-07-20_dimensions.sql    4 colonne numeriche + indici
MODIFICATI
  02_free_ads/02_insert_ad.php               4 campi nel form
  02_free_ads/02_01_upload_advertising.php   legge/valida/salva le misure
  02_free_ads/02_save_draft.php              misure nella bozza ospite
  sidebar_facets.php                         filtro lunghezza
  browse.php                                 applica il filtro (free+premium)
  shared/view_ad.php                         mostra le misure nel dettaglio
  lang/en.php it.php fr.php de.php            facet.len_min/len_max

## Ordine di applicazione
1. Applica 2026-07-20_dimensions.sql (idempotente).
2. Carica i file.
3. Pubblica un annuncio inserendo p.es. Length 800 (cm): nel dettaglio vedrai
   "Length: 8.00 m".
4. In browse, filtro "Min length 5 / Max length 10 (m)": vedrai solo gli
   annunci tra 5 e 10 m (quelli senza lunghezza non compaiono, e' corretto).
