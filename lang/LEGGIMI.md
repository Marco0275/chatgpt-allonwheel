# Sidebar tradotta (EN/IT/FR/DE) + allineamento lang

## Perche' la sidebar non si traduceva
`sidebar_user_box.php` stampava le etichette in inglese HARD-CODED (nessuna `te()`),
quindi restava in inglese in tutte le lingue.

## Cosa ho fatto
- `sidebar_user_box.php`: ogni etichetta ora passa da `te('sb.xxx','English')`.
  + guard i18n (`if(!function_exists('te')) require config/i18n.php`) e aggiunta di
  `05_wanted` e `07_rent` al rilevamento automatico di `$base_url` (robustezza link).
- `lang/en.php`: adottato il TUO file come base (contiene i tuoi 3 ritocchi) + 18 chiavi `sb.*`.
- `lang/it.php` / `fr.php` / `de.php`: aggiunte le 18 chiavi `sb.*` tradotte e allineati i
  3 ritocchi (home.fam_sub, home.fam_em, about.paddock1: tolto "cinque/cinq/funf").

## Nota
Tutte e 4 le lingue restano con lo STESSO set di chiavi (ora 341 + 18 = 359).
Le traduzioni IT/FR/DE della sidebar sono adattabili a piacere.
