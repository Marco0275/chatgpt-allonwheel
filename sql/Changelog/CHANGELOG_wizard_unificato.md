# Allonwheel - Inserimento annuncio UNIFICATO (free + premium) - 2026-07-07

Un solo ZIP. CRLF. PHP 8.3 lint: 786/786 OK. Test di routing free/premium
superato. Nessuna modifica al DB (le tabelle 02_free_ads / 03_ads restano).

## Cosa e' stato unificato
Un SOLO wizard in `02_free_ads/` serve sia gli annunci free sia i premium.
La "listing type" scelta al passo 1 decide tabella e limiti:
- **free**  -> tabella 02_free_ads, gallery **max 3 foto**;
- **premium** -> tabella 03_ads, gallery **max 20 foto**, + 2 step extra
  (scheda tecnica + documenti) che restano in `03_ads/`.
Lo stato viaggia in sessione (`aow_listing`) con rinforzo via `?lt=prem` nei
redirect: robusto anche se l'utente ricarica una pagina intermedia.

### Flusso
1. `02_00_select_type.php` - se l'utente puo' fare premium (Silver/Gold),
   compare il toggle **Standard / Premium**; i Basic vedono solo Standard.
   Il gate ora blocca solo chi non puo' inserire NULLA.
2. `02_insert_ad.php` - stesso form per entrambi; titolo/step adattati.
3. `02_01_upload_advertising.php` - INSERT nella tabella giusta; salva il
   tipo listing in sessione.
4. `02_insert_ad_image.php` / `02_01_upload_ad_image.php` - foto principale,
   cartelle upload_image/<tabella>/.
5. `02_insert_ad_gallery.php` / `02_01_upload_gallery.php` - gallery con
   limite 3 (free) o 20 (premium).
6. Al termine gallery: i **free** vanno alla preview; i **premium**
   proseguono su `03_ads/03_insert_tech_details.php` (scheda tecnica) e da li'
   al flusso documenti/preview premium gia' esistente.

## Modifica annunci (tuo requisito) - ora completa
- **Foto principale modificabile**: "Replace main image" nei form
  `02/03_modify_insert_ad.php` (gia' introdotto, verificato presente).
- **Cancellazione immagini dalla gallery - BUG RISOLTO**: la cancellazione
  e' ora un handler UNICO parametrico (`02_02_delete_image_gallery.php`,
  free+premium) con la tabella gallery corretta (prima il ramo premium era
  scollegato). `03_02_delete_image_gallery.php` e' un sottile wrapper che
  forza il ramo premium e delega. Il redirect torna alla pagina giusta
  (modifica vs inserimento) tramite un flag `ret`.
- Limiti gallery in modifica allineati: 3 (free) / 20 (premium).

## File mantenuti dove dici tu
- **02_free_ads/** = il wizard unificato (serve free e premium).
- **03_ads/** = SOLO i file che "completano" il premium: scheda tecnica,
  documenti, preview/view/modify tech, PDF, delete annuncio, ecc.
- I vecchi entry-point premium di inserimento (7 file) sono diventati
  **stub 301** verso il wizard unico (niente link rotti, dir. 19).

## cleanup_wizard_unificato.bat
Cancella i 7 stub premium + 4 sidebar per-pagina orfane. NON tocca
upload_image/ ne' images/. Da lanciare dalla radice del sito, dopo backup.
Se preferisci tenere i redirect attivi, non eseguirlo (gli stub sono
innocui). NON cancella 03_02 (wrapper vivo) ne' i file tech/documents.

## File in questo ZIP (23)
02_free_ads/: 02_00_select_type, 02_insert_ad, 02_01_upload_advertising,
  02_insert_ad_image, 02_01_upload_ad_image, 02_insert_ad_gallery,
  02_01_upload_gallery, 02_02_delete_image_gallery,
  02_modify_insert_ad_gallery, 02_01_modify_upload_gallery
03_ads/: 03_00_select_type, 03_insert_ad, 03_01_upload_advertising,
  03_insert_ad_image, 03_01_upload_ad_image, 03_insert_ad_gallery,
  03_01_upload_gallery (STUB 301); 03_02_delete_image_gallery (wrapper);
  03_modify_insert_ad_gallery, 03_insert_tech_details,
  03_01_upload_tech_advertising (redirect legacy aggiornati)
01_login/my_posts.php - sidebar_user_box.php (link -> wizard unico)
+ cleanup_wizard_unificato.bat

## Test rapidi (sul server)
1. Utente Basic: "Insert Free ad" -> nessun toggle premium; gallery si ferma
   a 3 foto; niente step tecnici.
2. Utente Silver/Gold: "Insert Premium ad" (o toggle Premium) -> l'annuncio
   finisce in 03_ads, gallery fino a 20, poi scheda tecnica + documenti.
3. Modifica un annuncio: cambia la foto principale -> si aggiorna.
4. Modifica gallery: cancella un'immagine (free e premium) -> sparisce e resti
   sulla pagina di modifica.
5. Vecchio URL 03_ads/03_00_select_type.php -> redirige al wizard unico.
