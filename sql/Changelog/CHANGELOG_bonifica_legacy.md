# Allonwheel - Bonifica 3 pagine legacy (redirect 301)

Data: 2026-06-24

Le 3 pagine non sono referenziate dalla navigazione: convertite/uniformate a stub
di redirect 301 robusti (come 00_first/*), cosi' i vecchi URL indicizzati o nei
bookmark vengono reindirizzati alla pagina reale e deindicizzati (noindex).

| File | Stato precedente | Ora -> target 301 |
|------|------------------|-------------------|
| `ad_post.php` | stub 301 senza fallback | uniformato -> `BASE_URL/browse.php` (+ noindex, fallback HTML) |
| `06_company/06_new_company.php` | fragile: `HTTP/1.1 301`, path hardcoded, no noindex | standard -> `BASE_URL/06_company/06_10_register_company.php` (+ noindex, fallback HTML) |
| `01_login/not_registered.php` | gia' stub standard | invariato -> `BASE_URL/01_login/newregister.php` |

Pattern standard: `require bootstrap` -> `header('Location:', true, 301)` +
`X-Robots-Tag: noindex` + fallback `<meta http-equiv="refresh">` + `exit`.
Nessuna modifica CSS: nessun bump cache necessario. Lint PHP 8.3 OK, CRLF.

Nota: i target (`browse.php`, `06_10_register_company.php`, `newregister.php`) esistono.
