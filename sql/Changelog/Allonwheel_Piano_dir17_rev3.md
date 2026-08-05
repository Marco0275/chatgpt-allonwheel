# Allonwheel — Aggiornamento Piano: **Direttiva 17 rev. 3** (modello sidebar ibrido)
*Rev. 9 giu 2026 — annulla e sostituisce la dir. 17 rev. 2 (sidebar per-sezione + header utente). Origine: flowchart Gemini caricato dall'utente.*

> Da incollare in `Allonwheel_Report_e_Piano.md` al posto della riga dir. 17 e in Parte 0/0-bis. Le altre direttive restano invariate.

---

## Cosa cambia rispetto alla rev. 2

La rev. 2 imponeva: ogni sezione ha la propria sidebar con **opzioni di sezione**, e **nessuna pagina personale** in alcuna sidebar (solo nell'header). Il nuovo flowchart reintroduce due elementi che la rev. 2 aveva eliminato:

1. **Contenuto condizionale al login** dentro la sidebar Marketplace (ospite vs loggato).
2. Una **sidebar di Area Personale** ("My account") per l'utente loggato — non più solo nell'header.

Inoltre distingue per la prima volta:

3. Pagine **editoriali a pagina intera** (Home / About / Portfolio) = **nessuna sidebar**.
4. Sezione **Blog** con una **propria sidebar** (Latest articles).

## Direttiva 17 (rev. 3) — testo nuovo

> **Sidebar ibrida per-sezione, con condizione di login dove serve.** Ogni sezione risolve la propria sidebar dal path corrente (non globalmente). Regole per sezione:
> - **Editoriale** (`index`, `about`, `what_we_do`, `portfolio`): **nessuna sidebar**, layout a pagina intera (`#no_sidebar`).
> - **Blog** (`blog`, `blog_post`, `blog_write`): sidebar `sidebar_blog.php` con *Latest articles* (dato reale) e CTA *Write an article* per i loggati.
> - **Marketplace** (`02`, `03`, `04`, `shared`, `browse`): sidebar `sidebar_marketplace.php` con le opzioni di sezione **più** un box CTA condizionale: ospite → *Register to sell*; loggato → *Sell on Allonwheel* (post free/premium, register company).
> - **Suppliers / Road / Special** (`06_company`, `road_vehicles`, `special_vehicles`, `shelter_container`): invariate (`sidebar_suppliers.php` / `sidebar_special.php`).
> - **Account** (`01_login`): ospite → opzioni di accesso; loggato → box *My account* (My posts, My profile, Upgrade to premium, Account settings, Logout). Le pagine personali restano **anche** nell'header.
> - Fallback: `sidebar_default.php`.

## Flowchart — nodo aggiunto

Il flowchart Gemini aggiunge un ramo **Blog / Events** sotto `Index`, non presente nel flowchart precedente:

```
Index
├── Login                → Account (sidebar condizionale)
├── Marketplace          → sidebar Marketplace (CTA condizionale)
│   ├── Free Ads
│   ├── Premium Ads
│   └── Request quotation
├── Suppliers            → sidebar Suppliers / Special (invariate)
│   ├── Company → Shelter / Container → Special
│   └── Project manager → Vehicle types → Road / Special
├── Portfolio            → pagina intera (NESSUNA sidebar)
├── About / What we do   → pagina intera (NESSUNA sidebar)
└── Blog / Events        → sidebar Blog (Latest articles)   ◄── NUOVO
```

## Mappa sezione ⇄ sidebar (risolta da `include_sidebar.php`)

| Sezione | Pagine | Sidebar | Login-aware |
|---|---|---|---|
| Editoriale | index, about, what_we_do, portfolio | *(nessuna)* | — |
| Blog | blog, blog_post, blog_write | `sidebar_blog.php` | sì (CTA scrittura) |
| Marketplace | 02_*, 03_*, 04_*, shared, browse | `sidebar_marketplace.php` | **sì** |
| Suppliers | 06_company/*, road_vehicles | `sidebar_suppliers.php` | no |
| Special | special_vehicles, shelter_container | `sidebar_special.php` | no |
| Account | 01_login/* | `sidebar_account.php` | **sì** |
| Fallback | FAQ, Conditions, contact, privacy, cookie-policy, 00_first, _admin | `sidebar_default.php` | parziale |

## Decisioni aperte (richiedono conferma utente)

1. **Posizione sinistra/destra.** Il flowchart indica sidebar a **sinistra** per Marketplace/Account e a **destra** per il Blog. Il CSS attuale (`#templatemo_content` float-left, `#templatemo_sidebar` float-right) mette **tutte** le sidebar a **destra**. Spostarle a sinistra richiede una regola CSS nuova → in conflitto con la **dir. 8** (solo foglio di stile esistente). Implementazione attuale: tutte a destra (dir. 8 rispettata). *Per la posizione a sinistra serve relax della dir. 8 con un'unica classe modificatore.*
2. **"Your favorites" (Preferiti).** Non esiste tabella né feature nel DB (dir. 14: niente dati inventati). Box omesso. Serve scaffolding (tabella `favorites` + endpoint) se richiesto.
3. **"Newsletter".** Nessun backend di iscrizione presente. Box omesso per non pubblicare un form non funzionante.
