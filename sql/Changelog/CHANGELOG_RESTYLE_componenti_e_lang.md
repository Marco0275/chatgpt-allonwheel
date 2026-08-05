# Allonwheel — Restyle 2026: completamento (componenti + traduzioni)

Data: 2026-06-24

Completa il restyle rendendolo coerente anche su liste, form e messaggi, e traduce
la home in tutte le lingue.

## allonwheel_style.css — addendum componenti (appeso dopo RESTYLE 2026)
- **Controlli form**: input/textarea/select e `.input_field` con bordo, raggio,
  padding e **focus ring** rosso. Polishing su login, registrazione, RFQ, contatti,
  pubblicazione annunci. Il campo ricerca header (ID) resta com'e'.
- **Badge a pill**: `.badge_premium` (rosso), `.badge_free`/`.badge_cond` (neutro),
  `.badge_type` (navy tenue) sulle liste annunci.
- **Messaggi**: `.error-msg` (alert rosso) + `.success-msg` (verde).
- **Liste annunci**: `.post_meta` come riga azioni con bordo superiore; `.gallery`/
  `.gallery.m0` con thumbnail arrotondate, ombra, object-fit.
- Le schede della **directory fornitori** usano `.post_box`, gia' a card.

## lang/{en,it,fr,de}.php — traduzioni home
Aggiunte 19 chiavi per lingua usate dalla home ridisegnata (hero, value props, CTA,
sottotitoli sezioni, wordmark/tagline): brand.tagline, home.hero_*, home.fam_sub,
home.all_h, home.vp1_*/vp2_*/vp3_*, home.b2b_sub, home.cta_*. Niente duplicati
(inserite solo se mancanti).

## Verifiche
- Full-project `php -l`: 0 errori. CSS: graffe bilanciate. CRLF preservati.

> Nota: questo pacchetto SOSTITUISCE `allonwheel_style.css` del pacchetto precedente
> (ora include anche l'addendum). Gli altri file del restyle (header.php, index.php,
> template_*, bump_css_version.sh) restano quelli gia' consegnati.
