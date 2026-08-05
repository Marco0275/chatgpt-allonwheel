# Allonwheel — Batch 1: punti 2, 3, 4

Base: Allonwheel_V_0_0_17. `php -l` OK su tutto il progetto. CRLF preservati. PDO+prepared.

## Punto 4 — Conteggio views (FATTO)
- **`shared/view_ad.php`**: ad ogni apertura di un annuncio incrementa
  `seller_statistics.views` (totali) e `unique_views` (unica per sessione, guard via
  `$_SESSION`), con upsert sulla UNIQUE `(ad_table,id_ads)`. In try/catch: non blocca
  mai la pagina. Vale per `02_free_ads` e `03_ads`. La dashboard venditore puo' ora
  mostrare anche le views (oltre ai download).

## Punto 3 — Traduzioni FR/DE (FATTO)
- **`lang/fr.php`** e **`lang/de.php`**: tutte le **255 chiavi** tradotte
  (francese e tedesco B2B). Macro e nomi-famiglia (Race Trailer, Hospitality,
  Mobile Clinic, Custom Projects) **restano in EN** come da convenzione brand;
  HTML interno (cond.rule*, citazioni) preservato. Entrambi caricano e lint OK.
  Ora le 4 lingue (en/it/fr/de) sono complete a livello di dizionario UI.

## Punto 2 — Clean URL (PROPOSTA da testare sul server)
- **`htaccess_clean_urls_PROPOSTA.txt`**: regole `mod_rewrite` per
  `/marketplace`, `/marketplace/<macro>`, `/suppliers`, `/wanted`, `/wanted/<id>`,
  `/road`, `/special`, `/sitemap.xml`. Vanno inserite **dopo** le regole lingua
  (che usano `[L]` e ri-ciclano il path). **Non ho toccato l'`.htaccess` live**:
  le rewrite non sono testabili qui e un errore romperebbe il routing (dir.19).
  Applicale e verificale sul server; gli annunci `/listing/<id>` richiedono un
  piccolo dispatcher (id non dice 02 vs 03) — batch separato se lo vuoi.

## Prossimi batch (gli altri tuoi punti)
- **Batch 2**: uniformare tutti i pulsanti allo stile "View details" (`button.more`)
  + propagazione dei vehicle types ovunque (filtri + hero) da un'unica sorgente DB.
- **Batch 3**: rifacimento sidebar (una per pagina, senza link gia' presenti nella
  pagina) — il piu' invasivo, lo isolo per qualita'/verifica.
