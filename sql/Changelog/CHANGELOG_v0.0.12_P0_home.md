# Allonwheel v0.0.12 — Delta P0: Home riallineata (70/30) + copy macro

Direzione attiva: **V0.0.11** (motorsport primario). Home riscritta con gerarchia
**70% motorsport (5 macro ufficiali) / 30% B2B veicoli commerciali e speciali**.

## index.php (riscritto)
- **SEO**: title/description/keywords motorsport-premium + B2B (no più demo template).
- **Sezione primaria ~70% (motorsport)**: lead + 5 post_box delle macro ufficiali
  `product_macros` → CTA reali `browse.php?macro=race-trailer|hospitality|mobile-clinic|shelter-container|custom-projects`.
- **Sezione secondaria ~30% (B2B)**: lead + 3 col_4 → `road_vehicles.php`, `special_vehicles.php`, `shelter_container.php`.
- **Hero (3 col_3)**: intro/marketplace/suppliers con CTA corrette (`browse.php`, directory,
  request a quotation). Search box ora punta a `browse.php`.
- **Rimossi tutti i link a `00_first/*`** (legacy in dismissione, P1) e a
  `02/03_view_ads.php`. Gallery dinamica "ultime ads" conservata.

### Immagini (dir. 15 rispettata)
- **Nessun file in `images/` toccato o creato; nessun path inventato** — tutti i path
  referenziati esistono (verificato).
- **Placeholder dichiarati** (asset dedicato assente): Mobile Clinic → `templatemo_image_05.jpg`,
  Shelter & Container (motorsport) → `templatemo_image_06.jpg`, Special vehicles →
  `templatemo_image_07.jpg`, Shelter B2B → `templatemo_image_08.jpg`, Road vehicles →
  `images/Boxtrailer.jpg`. **Sostituibili appena fornisci gli asset reali** (cambio solo i path).

## sql/Changelog/macro_intro_text.sql (nuovo)
- Popola `product_macros.intro_text` per le 5 famiglie → le intro compaiono su
  `browse.php?macro=` (browse.php le stampa al rigo 187).
- **Idempotente** (solo UPDATE per slug), MySQL 5.7, nessuna ALTER, nessun dato distrutto.

## Verifica (doppio passaggio, dir. 2/10)
- `php -l index.php` → No syntax errors. CRLF preservato (anche sul .sql).
- Tutti i path `images/` esistenti; 0 link a pagine `00_first`/`02_03_view_ads`.
- Bilanciamento `<div>` = stesso pattern del template (wrapper chiuso in footer.php).
- 5 CTA macro presenti e corrette.

## Ordine di applicazione
1. Eseguire `sql/Changelog/macro_intro_text.sql` sul DB.
2. Deployare `index.php`.
Nessun impatto su `upload_image/`, `images/`, altri file.

## Resta aperto (prossimi blocchi)
- **hero_image** delle macro (asset reali) e sostituzione dei placeholder.
- **P1**: redirect 301 di `00_first/*` e `02/03_view_ads.php` + bonifica riferimenti residui in `sidebar_*`, `footer.php`, `session_helper.php`.
- Allineamento dei 3 report DOCX in `report/`.
