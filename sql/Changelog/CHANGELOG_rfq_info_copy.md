# Allonwheel - RFQ: copia separata a info@allonwheel.com - 2026-06-25

Audit dei form di richiesta. Obiettivo: ogni richiesta deve arrivare anche a
info@allonwheel.com, in una MAIL SEPARATA.

## Stato per canale
- **RFQ** (`04_request_offer` -> `04_send_offer.php`): GIA' conforme in V_1_2.
  Oltre alle mail ai fornitori e alla copia a rfq@, invia una mail SEPARATA a
  `info@allonwheel.com` (riga 243, subject "Request an offer (info copy)"),
  inviata sempre per ogni richiesta.
- **Contatto generico** (`contact.php` -> `contact_submit.php`): info@ e' gia'
  il destinatario diretto.
- **Contatto professionista** (`contact_professional.php`): inviava SOLO al
  venditore. **Aggiunta** una mail separata a info@ ("Professional contact
  (info copy)") per dare alla piattaforma visibilita' di ogni richiesta.

La scheda annuncio (`shared/view_ad.php`) non ha un handler proprio: il percorso
RFQ passa dalla CTA globale "Request a quotation" -> `04_send_offer.php`, quindi
gia' coperto.

## Nota
`05_wanted/wanted_view.php` invia al COMPRATORE quando un venditore risponde a
una richiesta "wanted" (verso->buyer): e' una risposta, non una richiesta in
ingresso, quindi non inclusa. Se vuoi anche quella copiata a info@, lo aggiungo.

Solo `contact_professional.php` modificato. Mailer::send (5 arg) coerente.
Lint PHP 8.3 OK, CRLF. Nessun CSS toccato.
