# Allonwheel — Sprint 2: Motore "Wanted" (richieste inverse) + matching

Base: tua ZIP (DB gia' migrato: tabella `wanted_ads` presente). `php -l` OK su tutto
il progetto. CRLF preservati. Codice nuovo in PDO+prepared. Dir.8 rispettata.

> Nota cartella: le pagine stanno in **`05_wanted/`**; la tabella e' `wanted_ads`
> (gia' in produzione con questo nome). Lieve deroga a dir.1 sul nome, scelta per
> non rinominare una tabella gia' deployata (sarebbe distruttivo, dir.19).

## File nuovi
- **`libs/wanted_ads.class.php`** — modello + matching (PDO): create / get /
  listActive / listByUser / setStatus / deleteOwned; matching su **macro**
  (annunci 02+03 approvati, **free inclusi**): `sellersForMacro`, `adsForMacro`,
  `activeWantedForMacro`; notifiche **one-to-one** `notifySellers` (alla
  pubblicazione di una wanted) e `notifyBuyers` (all'approvazione di un annuncio).
- **`05_wanted/wanted_post.php`** — form "Cerco mezzo" (login): categoria (macro,
  obbligatoria), vehicle type (opz.), budget, paese, descrizione. CSRF, validazione.
  Alla pubblicazione **notifica i venditori** con annunci compatibili.
- **`05_wanted/wanted_list.php`** — elenco pubblico delle richieste attive, con
  filtro per macro.
- **`05_wanted/wanted_view.php`** — dettaglio; il venditore loggato puo'
  **rispondere** (email one-to-one al buyer); il proprietario vede gli annunci che
  potrebbero combaciare.
- **`05_wanted/wanted_manage.php`** — "My wanted": chiudi / riapri / elimina le
  proprie richieste (CSRF, ownership).

## File modificati
- **`header.php`** — `05_wanted` aggiunto a `$base_url`; voce **"Wanted requests"**
  nel menu Marketplace.
- **`sidebar_user_box.php`** — link "Post a wanted request" + "My wanted requests".
- **`_admin/moderate_ads.php`** — all'**approvazione** di un annuncio, notifica i
  buyer con wanted attive sulla stessa macro (in try/catch, non blocca la moderazione).

## Sicurezza (audit P0)
- Allegato **`AUDIT_P0_csrf_idor.md`**: esito = **P0 chiuso** (CSRF/IDOR gia' coperti
  nel legacy; reset password via token; script purge CLI/CRON_TOKEN).

## Note / prossimi passi
- Le **email** partono solo con SMTP attivo (`.env`).
- Da fare: integrare i Wanted in **`my_posts.php`** (dir.3, vista aggregata) — non
  incluso qui per non toccare il loop di render aggregato senza una verifica a parte.
- `seller_statistics` e la **dashboard venditore** (Sprint 3) useranno questi dati.
