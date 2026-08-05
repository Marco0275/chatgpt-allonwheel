# Allonwheel — PDF scheda completa + notifiche forum

Base: tua ZIP (con vendor/mpdf). 3 file. `php -l` OK su tutto il progetto. CRLF preservati.

## 1) PDF: ora riproduce l'INTERA scheda (non più un elenco)
`03_ads/03_tech_pdf.php` riscritto. Il PDF (`ad-<id>.pdf`) ora contiene:
- **Titolo + sottotitolo**.
- **Immagine principale** (original).
- **Dettaglio annuncio** (come view_ad): Author, Type, Condition, List price (€),
  Categories (dai flag dell'annuncio: Racing, Hospitality, Medical, ...).
- **Description** completa.
- **Technical specifications RAGGRUPPATE** come la pagina tech (`03_view_tech_details`):
  Number of cars, General options, Lift facilities, Cargo facilities, Office furniture,
  Electrical system, Outside finishing, Chassis, External dimension. Mostra solo le
  voci valorizzate (flag attivi e campi con valore).
- **Gallery**: griglia delle miniature (`03_ads_gallery`).
- Immagini incorporate via **percorso filesystem** (`DOCUMENT_ROOT/upload_image/03_ads/...`),
  così mPDF le legge lato server; le immagini mancanti vengono semplicemente saltate.
- CSS **inline proprio del documento** (un PDF non può usare il CSS del sito).
  Nessuna modifica a `images/`/`upload_image/` (solo lettura, dir. 15).

> Il PDF non è stato esteso a nessun altro flusso (come richiesto).

## 2) Prossimo passo — Forum: notifica email ai partecipanti
Quando viene postata una **nuova risposta** in un thread (commento del blog/forum):
- nuovo metodo `BlogManager::getThreadParticipantEmails()` raccoglie autore +
  tutti i commentatori (escluso chi ha appena scritto), con email valida;
- `blog_comment_save.php` invia a ciascuno una **email one-to-one** (un solo
  destinatario per messaggio) con il **link alla conversazione**.
- L'invio è in try/catch isolato: se la mail fallisce, **il commento viene comunque
  salvato** (la notifica non blocca il flusso). Richiede **SMTP attivo** per partire
  davvero (altrimenti fallback `mail()`).

## Resto da fare (prossimi step)
- **Invio elenco PM/consulenti** alle aziende con `wants_pm_list = 1`
  (usa `UserRoles::getUsersByRole`).
- Eventuale evidenziazione/ordinamento risposte Esperto nel thread (il badge c'è già).
