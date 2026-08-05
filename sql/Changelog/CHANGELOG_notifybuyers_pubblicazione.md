# Allonwheel — I buyer vengono avvisati alla pubblicazione (buco chiuso)
17 lug 2026. Un solo ZIP. CRLF. PHP lint 795/795 OK.

===========================================================
IL BUCO CHE CHIUDO
===========================================================
notifyBuyers (avvisa i buyer con una wanted attiva quando esce un annuncio
compatibile) scattava SOLO sull'azione "approve" in _admin/moderate_ads.php.
Ma gli annunci nascono status='approved' e NON passano dalla moderazione:
sono pubblici all'istante. Conseguenza: sul percorso normale i buyer non
venivano MAI avvisati. La funzione esisteva ma, di fatto, era morta.

Ora notifyBuyers parte anche alla PUBBLICAZIONE, dentro il wizard, subito
dopo l'INSERT riuscito (accanto al delete della bozza del punto 2).

===========================================================
COME L'HO INNESTATO SUL FILE FRAGILE
===========================================================
Dentro if($lastId){...}, cioe' DOPO che l'INSERT e' gia' andato a buon fine.
Le variabili che servono ($product_macro, $vehicle_type, $id_user, $aow_tbl,
$lastId, $title) sono TUTTE gia' calcolate a quel punto: nessun nuovo calcolo,
nessun accesso a POST. Blocco ADDITIVO in try/catch: un errore nell'invio
non ferma il wizard, l'annuncio e' gia' salvato.
Verificato: $aow_tbl (riga 29) e l'INSERT (riga 162) INVARIATI.

===========================================================
UNA COSA CHE HO DOVUTO GESTIRE: LA LATENZA
===========================================================
Mailer::send e' SINCRONO (SMTP o mail()). Con SMTP autenticato, ogni email
costa. Se 200 buyer hanno una wanted attiva su quella macro, sarebbero 200
invii durante il wizard -> pubblicazione lentissima.

Ho aggiunto una salvaguardia (NON un tetto anti-spam): AOW_WANTED_NOTIFY_MAX,
default 50. I buyer sono ordinati per pertinenza (vehicle_type esatto prima),
quindi i 50 piu' rilevanti vengono avvisati subito. E' diverso dal tetto
stretto dei venditori (AOW_RFQ_MAX_RECIPIENTS=3): quello e' anti-spam, questo
e' solo anti-blocco, perche' un buyer con wanted attiva HA chiesto di essere
avvisato e non va limitato per principio, solo per non bloccare il wizard.
  AOW_WANTED_NOTIFY_MAX = 0   -> nessun limite
  AOW_WANTED_NOTIFY_MAX = 50  -> default

Nota: la strada pulita a regime e' una CODA email (il wizard mette in coda,
un cron invia). L'infrastruttura non c'e' ancora; quando SMTP sara' attivo e
i volumi cresceranno, e' il passo successivo naturale. Per ora, con pochi
buyer in fase di lancio, l'invio sincrono con cap 50 e' piu' che sufficiente.

===========================================================
FILE IN QUESTO ZIP (2)
===========================================================
02_free_ads/02_01_upload_advertising.php  + notifyBuyers dopo l'INSERT
libs/wanted_ads.class.php                 + salvaguardia latenza (cap 50)

## Come verificare
1. Crea una wanted (buyer A) su una macro/vehicle_type.
2. Con un altro utente pubblica un annuncio di quella macro/vehicle_type.
3. Buyer A deve ricevere l'email "A new listing matches your wanted request"
   SENZA che un admin approvi nulla (prima non arrivava).
4. Il wizard prosegue normalmente allo step foto: la pubblicazione non si
   blocca anche se l'invio email tarda.
