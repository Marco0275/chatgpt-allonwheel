# Allonwheel - HOTFIX: "Undefined variable $aow_tbl" / "Incorrect table name ''"
2026-07-08. Un solo ZIP. CRLF. PHP 8.3 lint OK. Test guard superato.

## Il problema (dal tuo errore in produzione)
02_01_upload_gallery.php andava in Fatal error: $aow_tbl non definita ->
nome tabella vuoto nella query -> PDOException 1103 "Incorrect table name ''".

## Causa
Quando le patch di unificazione sono state applicate al file sul server, la
riga che DEFINISCE $aow_tbl (e $aow_lt/$aow_max) non e' entrata - mentre le
sostituzioni che la USANO (`02_free_ads` -> `' . $aow_tbl . '`) si': risultato,
una variabile mai valorizzata. (Tipico dei merge quando un anchor non combacia
per differenze di CRLF/tab/trattini.)

## La soluzione: GUARD idempotente e a prova di merge
In tutti i file del wizard che usano $aow_tbl, all'inizio (dopo il calcolo di
$id_ads, PRIMA di ogni query) c'e' ora un blocco che RICALCOLA sempre le
variabili se mancano o non sono valide:

    if (!isset($aow_lt) || ($aow_lt !== 'free' && $aow_lt !== 'prem')) {
        $aow_lt = ((($_POST['lt'] ?? $_GET['lt'] ?? $_SESSION['aow_listing'] ?? 'free')) === 'prem') ? 'prem' : 'free';
    }
    $_SESSION['aow_listing'] = $aow_lt;
    $aow_tbl = ($aow_lt === 'prem') ? '03_ads' : '02_free_ads';
    $aow_max = ($aow_lt === 'prem') ? 20 : 3;

$aow_tbl non puo' piu' essere vuoto: nel caso peggiore (nessun input) vale
'02_free_ads'. Testato con input assenti/garbage: sempre una tabella valida.

## In piu' - BUG LATENTE trovato e corretto
`02_01_modify_upload_gallery.php` era ancora HARDCODED su `02_free_ads`:
modificare la gallery di un annuncio PREMIUM avrebbe letto/scritto dalla
tabella sbagliata. Ora e' parametrizzato su $aow_tbl (+ limite 3/20 dinamico).
Rimosse anche definizioni duplicate residue in 3 file (innocue, ma pulite).

## File (6)
02_free_ads/: 02_01_upload_gallery, 02_01_upload_ad_image,
  02_insert_ad_gallery, 02_insert_ad_image, 02_02_delete_image_gallery,
  02_01_modify_upload_gallery
Sovrascrivere quelli sul server. Nessuna modifica al DB.

## Perche' questo risolve DEFINITIVAMENTE
Anche se in futuro una patch venisse applicata male, il guard ricostruisce le
variabili: il wizard non puo' piu' andare in "Incorrect table name ''".

## Test rapidi
1. Inserisci un annuncio FREE, arriva allo step gallery, carica una foto ->
   nessun errore, foto salvata (max 3).
2. Inserisci un annuncio PREMIUM (Silver/Gold), step gallery -> max 20, va su 03_ads.
3. Modifica la gallery di un annuncio premium: carica/cancella foto -> opera su 03_ads.
