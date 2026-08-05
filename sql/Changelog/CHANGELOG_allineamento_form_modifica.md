# Allonwheel — Form di modifica allineati a sinistra
23 lug 2026. UN SOLO ZIP. CRLF. PHP lint OK. Base: Allonwheel_3_3_variabili.
Aggiorna il pacchetto "modifica per sezione": stessi 14 file, con la correzione.

=================================================================
IL PROBLEMA
=================================================================
Nelle pagine di modifica appena generate, etichette e campi finivano spinti a
DESTRA invece che allineati a sinistra come nel form di inserimento.

CAUSA: nel foglio di stile le regole che impaginano i campi non stanno sulla
classe .form_row (che da sola non ha quasi CSS: esiste solo
#comment_form .form_row), ma sotto il contenitore #contact_form:

  #contact_form:not(.ad_detail):not(.tech_view) label{ display:block; margin-bottom:6px; }
  #contact_form:not(.ad_detail):not(.tech_view) .input_field{ width:100%; }

Il form di inserimento (02_insert_ad.php) e' infatti avvolto in
<div id="contact_form">. Le mie pagine di modifica no: senza quel contenitore
le etichette restavano in linea e i campi senza larghezza, con il risultato
visto a schermo.

=================================================================
LA CORREZIONE
=================================================================
shared/ad_modify_page.php ora rispecchia ESATTAMENTE la gerarchia
dell'inserimento:

  #templatemo_content
    <h2>            (titolo)
    <p>             (sezione + nota)
    <div id="contact_form">
      <form> ... campi ... </form>
    </div>

Cosi' le etichette tornano sopra il campo, i campi a piena larghezza e tutto
allineato a sinistra, identico al primo inserimento. Nessuna regola CSS nuova
e nessuno stile inline (dir. 8): si riusa il contenitore che il progetto gia'
prevede per i form.

La correzione vale per TUTTE le pagine di modifica generate - le tre sezioni
free e le tre premium - perche' condividono questa unica pagina.

=================================================================
NOTA SUL FORM TECNICO (premium)
=================================================================
Sulla pagina premium ci sono due form: dati annuncio e scheda tecnica.
#contact_form e' un ID e non puo' comparire due volte nella stessa pagina,
quindi il form tecnico resta nel suo .post_box. Verificato che non perde
stile: la scheda tecnica e' una tabella .tbl_collapse (che ha il suo CSS) e i
campi testo sono coperti dalla regola globale
  body .input_field, body textarea, body select { ... }
quindi bordo, padding e focus restano quelli del sito.

=================================================================
FILE IN QUESTO ZIP (14)
=================================================================
Gli stessi del pacchetto "modifica per sezione", con ad_modify_page.php
corretto. Se non hai ancora applicato quel pacchetto, questo lo sostituisce
integralmente.

  libs/ad_section_fields.class.php
  shared/ad_modify_page.php            <-- la correzione e' qui
  shared/ad_modify_fields.php
  shared/tech_details_fields.php
  02_free_ads/02_modify_road.php · 02_modify_special.php · 02_modify_shelter.php
  02_free_ads/02_modify_insert_ad.php (smistatore)
  02_free_ads/02_01_upload_advertising_modified.php
  03_ads/03_modify_road.php · 03_modify_special.php · 03_modify_shelter.php
  03_ads/03_modify_insert_ad.php (smistatore)
  03_ads/03_01_upload_advertising_modified.php

## Come verificare
Apri My posts -> Edit su un annuncio: il form deve avere l'etichetta SOPRA il
campo e i campi a piena larghezza, allineati a sinistra, esattamente come la
pagina di inserimento. Vale per tutte e tre le sezioni, free e premium.
