# Allonwheel — Batch D: badge Expert, account roles, 4 lingue, admin hero, sidebar, cleanup

Base: tua ZIP `Allonwheel_V_0_0_13_i18n.zip`. 20 file (5 nuovi) + procedura mPDF.
`php -l` OK su 190 file. CRLF preservati.

---

## 1) Ruolo Esperto con badge + Account settings (multi-ruolo)
- **Pagina nuova `01_login/account_roles.php`** ("Account settings → Account roles"):
  l'utente sceglie liberamente i ruoli **Esperto / Project manager / Consulente**
  (checkbox, multi-ruolo, CSRF). Salva via `UserRoles::addRole/removeRole`.
  Link aggiunto nel box "My account" della sidebar (`sidebar_user_box.php`).
- **Badge "Expert"** (classe CSS esistente `.badge`, dir. 8) mostrato accanto
  all'autore nei **post** (`blog_post.php`) e nei **commenti** (`blog_comments.php`)
  quando l'autore ha il ruolo Esperto. Nuovo metodo `UserRoles::hasRolePdo()`.
- **Frase nel form di registrazione** (`01_login/newregister.php`): cita l'opzione e
  dove trovarla → *"After registering you can become an Expert, Project manager or
  Consultant at any time from Account settings → Account roles."*

## 2) mPDF — verificato: NON funzionante (manca il necessario)
Confermato: c'è `libs/mpdf/src/` ma **mancano le dipendenze** e `vendor/autoload.php`.
**Procedura passo-passo per principiante** nel file allegato **`PROCEDURA_mPDF.md`**
(Strada A con Composer: installa Composer → apri cmd nella cartella → `composer
require mpdf/mpdf` → carica `vendor/` sul server → avvisami e collego la generazione PDF).

## 3) Cleanup — file `.bat` da eseguire
**`cleanup_useless_files.bat`** (doppio click dalla cartella radice del progetto):
rimuove le **57 cartelle `_notes`** (Dreamweaver, con `dwsync.xml`) e i manifest di
build alla radice. **NON tocca `images\`, `upload_image\` né `lang\`** (immagini e
dizionari). Salta esplicitamente i `_notes` dentro `images/` e `upload_image/` (dir. 15).

## 4) Quattro lingue (IT / FR / EN / DE)
- `config/i18n.php`: `AOW_LOCALES = ['en','it','fr','de']`.
- **`lang/fr.php` e `lang/de.php` creati** (base EN con tutte le chiavi, **da tradurre**;
  finché non tradotti, mostrano l'inglese via fallback di `t()`).
- `.htaccess`: rewrite locale esteso a `^(en|it|fr|de)/...`. Gli `hreflang` si
  generano già da `AOW_LOCALES` (coprono le 4 lingue in automatico).
- Il `.bat` di cleanup **non cancella** i dizionari.

## 5) Admin: upload hero image delle macro (+ intro IT)
- **Pagina nuova `_admin/admin_macros.php`** (voce nav "Hero images"): per ognuna
  delle 5 macro, **form di upload** dell'immagine hero + campo **Italian intro**.
  L'upload salva in **`/upload_image/macros/`** (non in `images/`, dir. 15) e
  **aggiorna `product_macros.hero_image`** nel DB; l'intro IT aggiorna
  `intro_text_it`. Validazione (jpg/png/webp, max 6 MB), CSRF, solo admin.
  Così gestisci tu hero e intro cliccando sulla macro, senza SQL manuale.

## 6) Sidebar — riesame con regola "no link già nel contenuto"
Riscritte **5 sidebar di sezione** con link **complementari, raggruppati per categoria**,
eliminando le ripetizioni col contenuto della pagina:
- **`sidebar_marketplace`**: prima ripeteva le 5 famiglie + "All listings" (già nel
  contenuto di browse) → ora **Suppliers** / **Sell** (post free/premium, registra
  azienda) / **Help** (FAQ, Conditions).
- **`sidebar_suppliers`**: prima ripeteva directory + tipi veicolo → ora **Marketplace**
  / **Company** (About, What we do, Portfolio) / **Help** (FAQ, Contact).
- **`sidebar_special`**: prima ripeteva le categorie special → ora **Marketplace** /
  **Suppliers** / **Help** (Request a quotation, FAQ).
- **`sidebar_account`**: **Explore** (Marketplace, Suppliers, Blog) / **Help**
  (FAQ, Conditions, Contact) — i link personali restano nel box "My account".
- **`sidebar_default`** (home/editoriali): la home già spinge Marketplace/famiglie →
  sidebar = **Company** (About, What we do, Contact) / **Help** (FAQ, Conditions,
  Request a quotation).
- `sidebar_blog` lasciata: la lista "Latest articles" è dinamica/complementare e su
  blog_post/blog_write i suoi link non duplicano il contenuto.
- Nuove chiavi `sb.*` in EN+IT (fallback EN per FR/DE).

---

## Promemoria azioni tue
- Esegui le due patch SQL dei batch precedenti (immagini macro + i18n/ruoli) se non
  fatto; per le hero ora puoi usare direttamente **Admin → Hero images**.
- `cleanup_useless_files.bat` (doppio click) per la pulizia.
- `composer require mpdf/mpdf` (vedi `PROCEDURA_mPDF.md`) per sbloccare il PDF.
- Imposta `HISTATS_ID` e l'SMTP nel `.env` (l'SMTP sblocca anche le notifiche del forum).
- Traduci `lang/fr.php` e `lang/de.php` quando vuoi (ora mostrano l'inglese).

## Prossimo blocco
Forum vero e proprio (thread/risposte su `blog`/`blog_comments` con l'Esperto) +
**notifiche email** ai partecipanti ad ogni risposta (richiede SMTP) + **invio elenco
PM/consulenti** alle aziende con `wants_pm_list=1`. Poi il PDF dopo mPDF.
