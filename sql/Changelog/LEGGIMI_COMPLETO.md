# NOLEGGIO — pacchetto COMPLETO (feature + integrazioni + hero)
Overlay unico e autosufficiente: applicandolo, il sito si allinea del tutto
(incluso: la card "Request a quotation" in home diventa "Vehicle rental").
Tutti i .php: php -l OK. CRLF preservati.

## PERCHE' vedevi ancora la card vecchia
Le modifiche del turno precedente erano corrette ma NON risultano applicate al sito:
questo pacchetto le racchiude tutte, quindi basta applicarlo.

## INSTALLAZIONE
1) SQL (in ordine, saltando quelli gia' eseguiti):
   - sql/Changelog/2026-07-26_rental_company.sql   (offers_rental + general_note su 06_company)
   - sql/Changelog/2026-07-26_rental_core.sql       (tabelle 07_rent_ads / _requests / _recipients)
   - sql/Changelog/2026-07-26_rental_admin.sql      (status richieste = lifecycle RFQ)
2) Cartelle upload scrivibili: upload_image/07_rent/original/ e .../thumbnail/
3) Sovrapponi tutti i file mantenendo la struttura.

## CONTENUTO / COSA FA
- Registrazione azienda: checkbox "offers rental" + nota + Note unico (06_10/06_11 + libs/06_company).
- Sezione 07_rent/: pubblica/vetrina/dettaglio + richiesta a checkbox + area lead (07_40) + engine (libs/rent.class.php).
- index.php: card value-prop "Vehicle rental" (al posto di "Request a quotation") + NUOVO hero.
- sidebar_user_box.php: link Post/Request rental + Rental leads (tradotti).
- browse.php: famiglia "Rentals". portfolio.php: sezione "Rentals" (galleria immagini da 07_rent_ads).
- header.php: voce menu "Vehicle rental" + 07_rent nel rilevamento base_url.
- _admin/rent_leads.php (+ voce menu): richieste noleggio trattate come le RFQ
  (elenco, filtri status new/distributed/quoted/won/lost, Sent/Tot, drill-down destinatari).
- lang/en|it|fr|de: tutte le chiavi (rental, sidebar, hero) tradotte.

## HERO — ho messo l'opzione A. Per cambiarla, in lang/*.php chiave 'home.hero_h':
- A (attiva): "Buy, sell and rent special vehicles"
- B:          "The marketplace for motorsport and special vehicles"
- C:          "Where the paddock buys, sells and rents"
(ricorda: il testo mostrato viene dai lang/, non dal default in index.php)
