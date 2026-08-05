# Allonwheel — Piani Free / Premium / Gold — Runbook di Deploy & QA

**Stato:** completato e certificato — 3 suite di test superate, lint OK su tutti i .php, nessuna regressione (`$conn` globale / MySQLi assenti).
**Radice server:** `…/htdocs/`  ·  **Convenzione file:** CRLF preservati, PDO unico driver.

---

## 1. Mappa funzionalità → file (con cartella)

| # | Funzionalità | Regola per piano | File (cartella) |
|---|---|---|---|
| 0 | Policy unica | fonte di verità dei limiti | `plan_policy.class.php` (**libs/**) |
| 1 | Limite annunci | Free 2 · Premium 10 · Gold ∞ | `user_tier.class.php` (**libs/**) |
| 2 | Profilo directory | Base testo · Avanzato logo+link+portfolio · Top in evidenza | `06_30_company_directory.php`, `06_02_view_company.php` (**06_company/**) |
| 3 | Posizionamento ricerca | Gold > Premium > Free | `browse.php` (**radice**), `view_ads.php`, `ad_card.php` (**shared/**) |
| 4 | Media per annuncio | Free 1 · Premium 10+tech+PDF · Gold ∞ | `02_insert_ad_gallery.php`, `02_01_upload_gallery.php` (**02_free_ads/**); `03_insert_ad_gallery.php`, `03_01_upload_gallery.php`, `03_insert_tech_details.php`, `03_documents.php` (**03_ads/**) |
| 5 | Badge | Premium "Premium" · Gold "Featured" | `ad_card.php`, `view_ads.php`, `view_ad.php` (**shared/**); `06_30`, `06_02` (**06_company/**); `allonwheel_style.css` (**radice**) |
| 6 | Wanted Requests | solo Premium/Gold | `wanted_list.php`, `wanted_view.php` (**05_wanted/**); `seller_dashboard.php` (**01_login/**) |
| 7 | Quotation in differita | Free +5g · Premium +3g · Gold immediata | `04_send_offer.php` (**04_request_offer/**); `rfq_deliver.php` (**cron/**); `seller_dashboard.php` (**01_login/**); `06_40_my_leads.php` (**06_company/**) |
| 8 | Blog | Free nulla · Premium risponde · Gold pubblica | `blog_write.php`, `blog_save.php`, `blog_comment_save.php` (**radice**) |
| 9 | Social | Free 0 · Premium 3/anno · Gold 12/anno (contatore) | `seller_dashboard.php` (**01_login/**); tabella `social_posts` (SQL) |

Migrazione: `2026-07-25_plan_limits.sql` (**sql/**).

---

## 2. Passi di deploy (ordine consigliato)

1. **Backup** DB + cartella `htdocs/`.
2. **Migrazione DB:** esegui `sql/2026-07-25_plan_limits.sql` (aggiunge `deliver_at`, crea `social_posts`, allinea `ad_limit_premium=10`).
3. **Upload file:** carica *tutti* i file del pacchetto ai percorsi corrispondenti (elenco §1). Sono interdipendenti: caricali insieme.
4. **Cron:** schedula la consegna differita, es. ogni ora:
   `0 * * * * php /…/htdocs/cron/rfq_deliver.php`
5. **IA social:** configura l'inserimento di una riga in `social_posts` (`user_id`, `posted_at`) a ogni pubblicazione — il contatore in dashboard sale da solo.

---

## 3. Verifica post-deploy (QA)

- [ ] Free: creazione bloccata al 3° annuncio; nessuna gallery (solo immagine principale); no tech/PDF; no Wanted; no pubblicazione/risposta blog.
- [ ] Premium: fino a 10 annunci totali; gallery 10; tech + planimetrie PDF; Wanted accessibile; risposta blog sì, pubblicazione no; badge "Premium".
- [ ] Gold: annunci illimitati; gallery illimitata; badge "Featured"; profilo in evidenza in directory; pubblicazione blog libera.
- [ ] Ricerca/browse: ordine Gold → Premium → Free.
- [ ] Directory: logo/link/portfolio nascosti ai Free; badge "Featured" ai Gold.
- [ ] RFQ generica: il Gold la riceve subito; Premium/Free compaiono in dashboard solo dopo 3/5 giorni (il cron le invia a scadenza).
- [ ] Dashboard: contatore social `usati/quota` per l'anno corrente.
- [ ] Controllo tecnico rapido (deve dare 0 righe):
      `grep -rn "bind_param\|new mysqli" htdocs/libs htdocs/04_request_offer htdocs/06_company`

---

## 4. Rollback
Ripristina il backup di `htdocs/` e del DB. La colonna `deliver_at` e la tabella `social_posts` sono additive: possono restare senza effetti collaterali anche con il codice precedente.
