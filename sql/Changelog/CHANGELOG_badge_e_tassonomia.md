# Allonwheel — Solo badge Premium + tassonomia Shelter unificata
16 lug 2026. Un solo ZIP. CRLF. PHP lint 791/791 OK. SQL MySQL 5.7.

===========================================================
1) BADGE: resta solo Premium, il "Free" sparisce
===========================================================
Cercati TUTTI i punti che mostravano il badge, non solo quelli che avevo
citato. Trovati e corretti:

  browse.php:415-417              card pubblica: Premium / Free -> solo Premium
  shared/family_page.php          non aveva alcun badge tier -> aggiunto Premium
  01_login/seller_dashboard.php   dashboard venditore: Premium / Free -> solo Premium

L'assenza del badge indica gia' l'annuncio standard: non si perde
informazione, e sulla dashboard del venditore diventa anzi una spinta
naturale all'upgrade ("il mio annuncio non ha il badge").

NON toccati (stessa classe CSS, significato DIVERSO):
  06_company/06_30_company_directory.php:138  badge_premium = "Founding partner"
  06_company/06_02_view_company.php:124       badge_premium = "Founding partner"
Riusano .badge_premium solo per il colore: non c'entrano col tier annuncio.

La classe CSS .badge_free ora non e' piu' usata da nessuna pagina viva.
L'ho LASCIATA nel foglio di stile: rimuoverla non porta nulla e tocca il CSS
inutilmente. Se un giorno vuoi ripristinare il badge, e' li'.

===========================================================
2) TASSONOMIA SHELTER: decisione presa (mi hai delegato)
===========================================================
DECISIONE: shelter_container.php passa da `item_kind = 'shelter_container'`
a `product_macro = 'shelter-container'`, come le altre 4 famiglie.

PERCHE' E' LA SOLUZIONE MIGLIORE (non e' un'opinione: sta nel codice)
Ho verificato la relazione fra i due campi prima di scegliere:

 a) libs/product_macro.class.php, ProductMacro::forAd():
        if ($kind === 'shelter_container') { return self::SHELTER; }
    e' il PRIMO controllo della funzione -> priorita' massima, nessun altro
    ramo puo' sovrascriverlo.
 b) sql/Changelog/product_macros.sql, backfill:
        WHEN `item_kind` = 'shelter_container' THEN 'shelter-container'
    prima riga del CASE -> stessa priorita' massima.

Conseguenza logica: OGNI annuncio con item_kind='shelter_container' ha gia'
product_macro='shelter-container'. Quindi il nuovo filtro e' un
SOVRAINSIEME del vecchio, non un sottoinsieme: non puo' far sparire
annunci, semmai ne mostra anche di corretti a mano in admin.

Vantaggi concreti:
 - Un solo meccanismo per tutte e 5 le famiglie (prima erano due).
 - product_macro e' INDICIZZATA (idx_02/03_product_macro), item_kind no:
   la query e' anche piu' veloce.
 - Le correzioni fatte da _admin/edit_ad.php sulla macro ora hanno effetto
   sulla pagina (prima venivano ignorate: filtrava per un altro campo).
 - La pagina e' il target del 301 di browse.php?macro=shelter-container e
   della voce di menu "Shelter & Container": ora mostra davvero cio' che la
   tassonomia dichiara. Prima poteva mostrare un insieme diverso.

RETE DI SICUREZZA: sql/Changelog/2026-07-16_shelter_macro_align.sql
Unico caso teorico di annuncio "perso": uno antecedente alla migrazione con
item_kind valorizzato e product_macro ancora NULL. La patch lo riallinea:
 - NON distruttiva (dir. 9): non cancella, non sovrascrive;
 - agisce SOLO dove product_macro IS NULL -> rispetta le macro impostate a
   mano in admin (stessa filosofia del backfill esistente);
 - idempotente: rieseguibile senza effetti;
 - MySQL 5.7 (verificato: 0 costrutti 8.0+).
Contiene anche, come commenti pronti da incollare:
 - una VERIFICA PRIMA ("quanti annunci rischiano di non comparire?": se
   torna 0 sei gia' a posto e la patch non fa nulla);
 - un CONTROLLO DOPO che elenca le righe dove item_kind dice shelter ma la
   macro dice altro. Non le tocco in automatico: potrebbero essere scelte
   deliberate fatte in admin. Guardale e decidi.

===========================================================
FILE IN QUESTO ZIP (5)
===========================================================
browse.php                            solo badge Premium
shared/family_page.php                + badge Premium (sovrascrive di nuovo)
01_login/seller_dashboard.php         solo badge Premium
shelter_container.php                 -> product_macro (tassonomia unificata)
sql/Changelog/2026-07-16_shelter_macro_align.sql   patch di allineamento

## Ordine di applicazione
1. Se non l'hai ancora fatto: applica "Allonwheel_pagine_famiglia.zip"
   e poi "Allonwheel_fix_search_famiglia.zip".
2. Applica i file di questo ZIP (shared/family_page.php sovrascrive di nuovo:
   e' la versione buona, con Search + badge Premium).
3. DB: esegui prima la VERIFICA PRIMA (commento in cima alla patch). Se
   restituisce 0 per entrambe le tabelle, la patch non serve ma eseguirla
   non fa danno. Altrimenti eseguila.
4. Prova /shelter_container.php: deve mostrare gli stessi annunci di prima
   (o qualcuno in piu' se c'erano macro corrette a mano).
5. Prova /browse.php: gli annunci standard non hanno piu' il badge "Free",
   i premium hanno "Premium".
