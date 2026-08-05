# Allonwheel - Seller dashboard: stato annunci + tier - 2026-06-24

La sezione listing del dashboard mostrava solo i download, senza lo stato di
pubblicazione ne' il tier: il venditore non vedeva a colpo d'occhio cosa fosse
live, in moderazione o rifiutato.

## Modifiche
- Query annunci estesa con `status` (su entrambe le tabelle 02_free_ads/03_ads
  nello UNION, e nel SELECT esterno).
- Sezione rinominata "Document downloads per listing" -> **"My listings"**.
- Per ogni annuncio, due badge (riuso classe `.badge`):
  - **Tier**: Premium (03_ads) / Free (02_free_ads) -> il free/premium resta un
    badge in coda, non nel titolo (coerente con dir.14).
  - **Status**: Approved (verde) / Pending review (ambra) / Rejected (rosso);
    stati ignoti -> badge neutro con etichetta capitalizzata.
- CSS: aggiunti `.badge_approved`, `.badge_pending`, `.badge_rejected`.

Restano invariati download/documenti e i link "manage". Lint PHP 8.3 OK, CRLF.
CSS bumpato a **?v=20260705**.
