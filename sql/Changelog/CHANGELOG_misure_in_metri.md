# Allonwheel — Database allineato alle misure in METRI
23 lug 2026. UN SOLO ZIP. CRLF. Testata su MariaDB 10.11 (compatibile 5.7).

Hai portato le misure a METRI nei file, rinominando i campi in
length_mt / width_mt / height_mt. Questa patch allinea il DATABASE.

=================================================================
PERCHE' NON E' UNA SEMPLICE RINOMINA
=================================================================
Le colonne attuali contengono CENTIMETRI. Nel tuo dump c'e' gia' almeno una
riga con 1000, 1000, 1000 (= 10 metri). Rinominando e basta, quel 1000
verrebbe letto come "1000 metri": un trailer da 10 m diventerebbe lungo
un chilometro. Quindi la patch CONVERTE i valori (/100), non li rinomina.

=================================================================
COSA FA (in ordine)
=================================================================
1. Aggiunge length_mt / width_mt / height_mt come decimal(6,2)
   su 02_free_ads e 03_ads.
   decimal e non float: valori esatti, niente arrotondamenti strani;
   (6,2) tiene fino a 9999.99 m con 2 decimali (12.5 -> 12.50).
2. CONVERTE i dati esistenti: length_mt = length_cm / 100.
   Agisce solo dove la colonna nuova e' vuota: se rilanci la patch NON
   ri-divide quanto gia' salvato in metri.
3. Sposta gli indici idx_02_length e idx_03_length da length_cm a
   length_mt (li usa il filtro dimensionale della sidebar).
4. Rimuove le vecchie colonne _cm, ma SOLO se ogni valore e' stato
   copiato. Se anche una sola riga non fosse migrata, la colonna resta
   e non perdi niente (dir. 9).

axles_n NON viene toccato: e' un conteggio di assi, non una misura.
wanted_ads non ha colonne misura (verificato sul dump).

=================================================================
TEST ESEGUITI (su database vero, non a occhio)
=================================================================
Ho installato MariaDB, ricreato le tue tabelle con i tipi esatti del dump
(smallint UNSIGNED + gli indici) e i dati reali, poi eseguito la patch.

Conversione:
   1000 cm -> 10.00 m      (la riga reale del tuo dump)
    750 cm ->  7.50 m
   1250 cm -> 12.50 m
    245 cm ->  2.45 m
   NULL    -> NULL         (misura non indicata resta non indicata)
Colonne _cm rimosse; tipi finali decimal(6,2); indici ora su length_mt;
axles_n intatto.

Idempotenza: patch eseguita 3 volte di fila, nessun errore e NESSUNA doppia
divisione (10.00 e' rimasto 10.00). Un annuncio inserito in metri fra una
esecuzione e l'altra (8.75) e' rimasto intatto.

Casi limite:
 - DB senza alcuna colonna misura (installazione vecchia): la patch crea le
   _mt e non fallisce.
 - Conversione incompleta (simulata): la colonna _cm NON viene rimossa e i
   dati restano. La salvaguardia funziona.

=================================================================
!! CONTROLLA QUESTO NEI TUOI FILE PHP !!
=================================================================
La validazione che c'era in V3.2 per le misure era pensata per i
CENTIMETRI INTERI:

    if ($raw === '' || !ctype_digit($raw)) { return null; }
    $n = (int)$raw;

ctype_digit() e' VERO solo per cifre: con i metri, "12.5" / "2.45" / "0.9"
sono FALSI e finiscono a NULL. Se hai solo rinominato i campi lasciando
questa validazione, l'utente scrive 12.5 e il valore viene scartato in
SILENZIO (nessun errore a video, misura vuota nel DB). Verificato: 12.5,
2.45 e 0.9 vengono tutti scartati.

Sostituiscila con questa (testata, gestisce anche la virgola italiana):

    // Misure in METRI. Decimali ammessi. Vuoto/non numerico -> NULL.
    $aow_mt = static function ($raw, float $max = 9999.99) {
        $raw = str_replace(',', '.', trim((string)$raw));   // accetta "12,5"
        if ($raw === '' || !is_numeric($raw)) { return null; }
        $n = round((float)$raw, 2);                          // decimal(6,2)
        return ($n > 0 && $n <= $max) ? number_format($n, 2, '.', '') : null;
    };
    $length_mt = $aow_mt($_POST['length_mt'] ?? '');
    $width_mt  = $aow_mt($_POST['width_mt']  ?? '');
    $height_mt = $aow_mt($_POST['height_mt'] ?? '');

    // axles_n resta un CONTEGGIO intero
    $axles_raw = trim((string)($_POST['axles_n'] ?? ''));
    $axles_n = ($axles_raw !== '' && ctype_digit($axles_raw) && (int)$axles_raw > 0
                && (int)$axles_raw <= 20) ? (int)$axles_raw : null;

Esito del test: 12.5->12.50, 2.45->2.45, 0.9->0.90, 12->12.00, "12,5"->12.50,
vuoto/abc/-3/0/99999 -> NULL.

Nei FORM, i campi metro vogliono i decimali:
    <input type="number" min="0" step="0.01" max="9999.99" name="length_mt" ...>
(se e' rimasto lo step intero o max="65000" ereditato dai cm, correggilo).

Da controllare in TUTTI e 4 i file: 02_insert_ad.php,
02_01_upload_advertising.php, 02_modify_insert_ad.php,
02_01_upload_advertising_modified.php (piu' i gemelli 03_ads).

Nota: anche il filtro dimensionale in sidebar/browse.php convertiva i metri
digitati in cm (*100) prima di interrogare il DB. Ora il DB e' gia' in metri:
quella moltiplicazione va TOLTA, altrimenti il filtro non trova nulla.

=================================================================
FILE IN QUESTO ZIP (1)
=================================================================
sql/Changelog/2026-07-23_ad_dimensions_meters.sql

## Come applicare
1. Backup del DB (e' una migrazione con conversione di dati).
2. phpMyAdmin -> database -> Importa -> carica il file -> Esegui.
3. Verifica:
     SHOW COLUMNS FROM `02_free_ads` LIKE '%_mt';   -- 3 colonne decimal(6,2)
     SHOW COLUMNS FROM `02_free_ads` LIKE '%_cm';   -- 0 righe
     SELECT id_ads, length_mt, width_mt, height_mt FROM `02_free_ads`
       WHERE length_mt IS NOT NULL;                 -- 1000 cm ora e' 10.00
4. Poi sistema la validazione PHP come sopra, altrimenti i decimali
   vengono scartati in silenzio.
