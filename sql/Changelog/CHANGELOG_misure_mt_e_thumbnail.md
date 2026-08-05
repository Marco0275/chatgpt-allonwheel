# Allonwheel — Misure in metri (verifica completa) + bordo thumbnail
23 lug 2026. UN SOLO ZIP. CRLF. PHP lint 283/283 OK. CSS graffe bilanciate.
Base: V3.2 (quella che hai caricato).

=================================================================
1) L'ERRORE A VIDEO SU browse.php
=================================================================
  Warning: Undefined variable $lmin_mt ... on line 187
  Warning: Undefined variable $lmax_mt ... on line 188

CAUSA: rinomina incompleta. Le righe 76-77 definivano $lmin_m / $lmax_m
(suffisso _m), le righe 187-188 usavano $lmin_mt / $lmax_mt (suffisso _mt).
Due nomi diversi: le variabili "usate" non esistevano, il warning compariva
in cima alla pagina e il filtro lunghezza NON scattava mai.

Secondo difetto nella stessa riga: (int)$_GET['lmin'] TRONCAVA i decimali,
quindi 12.5 diventava 12. Con le misure in metri e' un errore di mezzo metro.

FIX: nomi allineati a _mt e lettura che accetta i decimali (coerente con la
colonna decimal(6,2)), inclusa la virgola italiana ("12,5" -> 12.5).

=================================================================
2) PERDITA SILENZIOSA DEI DECIMALI (il piu' grave)
=================================================================
I tre handler validavano le misure con la funzione scritta per i CENTIMETRI
INTERI:
    if ($raw === '' || !ctype_digit($raw)) { return null; }
    $n = (int)$raw;
ctype_digit() e' FALSO su "12.5", "2.45", "0.9". Risultato: l'utente scriveva
12.5 metri, nessun errore a video, e nel database finiva NULL. Verificato.

FIX in 02_01_upload_advertising.php, 02_01_upload_advertising_modified.php e
03_01_upload_advertising_modified.php: nuovo validatore $aow_mt che accetta i
decimali, arrotonda a 2, limita a 9999.99 (il massimo di decimal(6,2)) e
normalizza la virgola. axles_n resta sul validatore INTERO: e' un conteggio
di assi, non una misura.
Test: 12.5->12.50, 2.45->2.45, 0.9->0.90, 12->12.00, "12,5"->12.50,
vuoto/abc/-3/0/99999 -> NULL.

=================================================================
3) I FORM RIFIUTAVANO I DECIMALI (lato browser)
=================================================================
I tre form avevano  max="65000"  (residuo dei centimetri) e NESSUN step:
un <input type="number"> senza step usa step=1, quindi il browser stesso
rifiuta 12.5 ancora prima di inviare.

FIX su 02_insert_ad.php, 02_modify_insert_ad.php, 03_modify_insert_ad.php:
    max="9999.99" step="0.01"   (9 campi in tutto)
axles_n resta max="20" intero. Le etichette dicevano gia' "(mt)": ok.

=================================================================
4) IL FILTRO LUNGHEZZA NON AVEVA PIU' UI
=================================================================
Trovato verificando: browse.php legge lmin/lmax, ma NESSUN form li generava
(in V3.1 i campi c'erano in sidebar_facets.php, in V3.2 sono spariti). Il
filtro era codice irraggiungibile - ed era proprio quello che generava il
warning. Ripristinati i due campi (Min/Max length) in sidebar_facets.php,
con step 0.01, e la lunghezza ora conta anche per il link "azzera filtri".
Le traduzioni facet.len_min/len_max esistevano gia' in EN/IT/FR/DE e dicono
gia' "(m)": nessuna modifica ai file lingua.

=================================================================
5) IL BORDO DELLA THUMBNAIL PIU' PICCOLO DELL'IMMAGINE
=================================================================
CAUSA (nel CSS, quindi valeva su TUTTE le pagine con thumbnail):
il foglio di stile ha un blocco storico e un "addendum" piu' recente.
Il blocco storico dava al <li>:
    width:220px; height:150px; border:1px solid #ccc; padding:5px;
L'addendum lo correggeva solo a meta':
    .gallery li{ margin:0; padding:0; background:none; }
azzerando margin e padding ma NON bordo e dimensioni. Con
box-sizing:border-box globale, nella card di browse il <li> restava alto
150px con il suo bordo #ccc, mentre l'<a> che contiene l'immagine e' 240x165:
l'immagine sporgeva di 15px sotto (e 2px a destra) e il bordo appariva piu'
piccolo di lei. In piu' c'erano DUE cornici sovrapposte e sfalsate.

FIX: una sola regola, il <li> torna neutro
    .gallery li{ ... border:0; width:auto; height:auto; float:none; }
La cornice resta dove il progetto la vuole: sull'<a> (o sull'<img> nella
scheda annuncio .ad_detail). L'immagine, con object-fit:contain, sta sempre
dentro il bordo: rettangolo perfetto, niente doppia cornice.

Verificati TUTTI i file che usano le gallery (19): 18 hanno il markup
<li><a><img></a></li> e sono coperti dalla cornice sull'<a>. Uno solo,
_admin/admin_hero.php, ha <li><img></li> senza <a> e sarebbe rimasto senza
cornice: aggiunta una regola per il solo caso "img figlio diretto di li".
Nei casi con <a> non si attiva, perche' .gallery li a img{border:0} e' piu'
specifica - verificato il calcolo di specificita', nessun doppio bordo.
Contesti speciali controllati: .gallery_box (schede azienda) e .ad_detail
(scheda annuncio, dove la cornice sta sull'img e l'immagine arriva a 280px:
prima sforava il <li> da 220px, ora no).

=================================================================
FILE IN QUESTO ZIP (9)
=================================================================
browse.php                                         variabili _mt + decimali
allonwheel_style.css                               fix bordo thumbnail
sidebar_facets.php                                 filtro lunghezza ripristinato
02_free_ads/02_insert_ad.php                       step 0.01 / max 9999.99
02_free_ads/02_01_upload_advertising.php           validatore metri
02_free_ads/02_modify_insert_ad.php                step 0.01 / max 9999.99
02_free_ads/02_01_upload_advertising_modified.php  validatore metri
03_ads/03_modify_insert_ad.php                     step 0.01 / max 9999.99
03_ads/03_01_upload_advertising_modified.php       validatore metri

## Come verificare
1. Apri browse.php: i due Warning in cima sono spariti.
2. Inserisci un annuncio con Length 12.5: il form lo accetta e nel DB trovi
   12.50 (prima diventava NULL senza dirti niente).
3. Modifica quell'annuncio: il campo mostra 12.50 e resta 12.50 al salvataggio.
4. In sidebar "Refine listings" ora ci sono Min/Max length (m): con 10 e 15
   l'annuncio da 12.5 compare, con Min 20 sparisce.
5. Guarda le thumbnail in browse: il bordo circonda l'immagine senza che
   questa sporga, e non c'e' piu' la seconda cornice grigia sfalsata.
