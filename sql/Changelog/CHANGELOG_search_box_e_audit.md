# Allonwheel — Fix box di ricerca + audit finale
16 lug 2026. Base: V2_2 reale. Un solo ZIP. CRLF preservati.
CSS bilanciato (472/472). Verificato con misurazione pixel dello screenshot
e con risolutore di cascata CSS (specificita' + !important + ordine).

===========================================================
IL BUG DEL BOX SEARCH — causa reale
===========================================================
Misurazione sui pixel del tuo screenshot:
  campo (pill bianca) : alto  18px  (+2px bordo = 20px)
  pulsante rosso      : alto  30px
  centro pulsante 5px PIU' IN BASSO del centro del campo
=> il pulsante e' PIU' ALTO del campo e sporge sotto. Ecco perche' "appare
   cosi'" in tutte le pagine (il markup del search box e' duplicato in ogni
   pagina, quindi il difetto e' ovunque).

CAUSA: il restyle sovrascriveva width/padding ma NON le ALTEZZE ereditate
dal template originale. Con box-sizing:border-box l'altezza resta bloccata:

  elemento          template originale        restyle            risultato
  #search_box       width:240 height:30       width:auto !imp    height:30 RESTA
  #search_box form  width:180 height:30       (nessuna regola)   form 180px limita tutto
  #searchfield      height:20 width:240       width:230 padding  height:20 RESTA ->
                    float:left                (nessuna height!)  campo 20px, padding schiacciato
  #searchbutton     height:28 width:40        width:30 height:30 30px > campo 20px -> SPORGE

In pratica il campo era alto 20px invece dei ~38px voluti, e il pulsante da
30px non ci poteva stare dentro. Anche la larghezza era sbagliata: il campo
prendeva i 180px del form invece dei 230px previsti.

FIX (solo CSS, nessuna classe nuova - dir. 8; markup invariato):
geometria resa DETERMINISTICA, senza piu' dipendere dalle altezze ereditate:
  #search_box            width:250px  height:auto  position:relative
  #search_box form       width:100%   height:auto  float:none   (annulla i 180px)
  #searchfield           width:100%   height:38px  padding:0 44px 0 16px
                         float:none   display:block            (i 44px a destra
                                                                sono lo spazio
                                                                per il pulsante)
  #searchbutton          30x30  position:absolute  right:4px
                         top:50% + translateY(-50%)  -> centrato verticalmente
Aggiunti: colore del placeholder (--muted, contrasto 5.46:1 = AA) e hover del
pulsante. Rimossa una regola duplicata (riga ~1449) che ripeteva il
posizionamento del pulsante: ora la geometria e' in UN SOLO blocco.
Media query <=860px: il box passa a larghezza piena (era gia' previsto, ora
coerente anche sul contenitore).

VERIFICA (risolutore di cascata sulle regole reali del file):
  width = 100% | height = 38px | padding = 0 44px 0 16px   <- tutte dalla
  regola #search_box input#searchfield (2 id + !important: batte la regola
  generica "body input[type=text]" che imponeva padding:10px 12px).
  => campo 38px, pulsante 30px: sta DENTRO con 4px sopra/sotto. Risolto.

===========================================================
COLORE DEL CARATTERE: NON va cambiato (dato oggettivo)
===========================================================
Hai chiesto di valutarlo. Ho calcolato i contrasti WCAG reali della palette:

  Testo corpo (--text #1d2733 su bianco)      15.11:1   AAA
  Testo corpo su sfondo pagina                13.95:1   AAA
  Titoli (--ink #0e1a2b)                      16.15:1   AAA
  Menu bianco su header scuro                 17.48:1   AAA
  Testo secondario (--muted #5d6b7a)           5.46:1   AA
  CTA bianco su rosso / link rossi             4.85:1   AA
  Tagline #9fb0c4 su header scuro              7.89:1   AAA
  "WHEEL" rosso su header scuro                3.61:1   OK come wordmark
                                                        (testo grande: soglia 3.0)

Conclusione: la leggibilita' e' gia' ottima, in gran parte AAA (il massimo).
Cambiare il colore del carattere non porterebbe alcun beneficio misurabile e
rischierebbe solo di peggiorare. NON lo tocco: se vuoi cambiarlo e' una scelta
estetica, non un problema da correggere.
Unico valore basso: --line (#e3e8ef) sui bordi delle card = 1.23:1, ma e' un
bordo decorativo affiancato da un'ombra, non un elemento informativo: a norma.

===========================================================
COSA RIMANE DA FARE (verificato sul codice, non a memoria)
===========================================================
### Dipende SOLO da te (3 task infra, 30 minuti in tutto)
1. HISTATS_ID: il partial includes/histats.php e' pronto e consent-gated, ma
   manca l'ID reale (costante o env). Senza, il contatore non parte.
2. URL social nel footer: Facebook e Instagram sono REALI; LinkedIn, YouTube
   e Vimeo sono ancora href="#" (righe 50-52). Servono gli URL veri, o vanno
   rimossi: un link social che non porta da nessuna parte danneggia la
   credibilita' piu' della sua assenza.
3. SMTP: Mailer legge tutto da env (MAIL_TRANSPORT=smtp + SMTP_*). Finche'
   non e' configurato con SPF/DKIM sul dominio, le email RFQ rischiano lo spam.

### Lacuna SEO REALE trovata ora (te la segnalo, non l'ho toccata)
4. index.php e browse.php NON includono includes/seo_head.php: sono le due
   pagine piu' importanti del sito (home e marketplace) e sono le uniche
   senza canonical e senza hreflang. Le hanno invece road_vehicles,
   special_vehicles, about, blog, portfolio, la directory fornitori e la
   scheda annuncio. Senza canonical, browse.php con i filtri (?macro=, ?q=,
   ?cat=) genera decine di URL che Google vede come pagine duplicate.
   E' il singolo intervento SEO col miglior rapporto valore/tempo (~1h).
   Mancano seo_head anche: shelter_container, contact, FAQ, Conditions,
   privacy, cookie-policy (meno critiche).
5. Doppia sitemap: esistono sitemap.php (dinamica, 5 query, e' quella
   dichiarata in robots.txt = giusta) e sitemap.xml (statica, 163 URL).
   La statica non contiene URL morti, ma essendo statica invecchia da sola
   (i nuovi annunci non ci finiranno mai). Da rimuovere per evitare ambiguita'.
6. robots.txt ha "Disallow: /upload_image/": blocca le FOTO degli annunci
   da Google Immagini. Per un marketplace di veicoli e' traffico buttato.
   Valuta di sbloccarlo (la cartella e' comunque protetta dal .htaccess).

### Proposta: selettore lingua in alto (la tua idea - condivisa)
Oggi sta SOLO nel footer (footer.php:61, aow_lang_switcher()). Le 4 lingue
(EN/IT/FR/DE) sono attive con 306 stringhe tradotte ciascuna: un lavoro
gia' fatto e completamente invisibile a chi non scorre fino in fondo.
Spostarlo/duplicarlo nell'header (in alto a destra, vicino alla CTA) e' un
miglioramento vero e a basso rischio: la funzione esiste gia' e restituisce
solo link, quindi serve una regola CSS e una riga in header.php.
Nota: i tuoi primi fornitori realistici sono italiani, quindi rendere l'IT
visibile in alto abbassa l'attrito proprio dove serve.
NON l'ho fatto: tocca l'header di tutto il sito e mi hai chiesto di proporlo.
Dimmi "procedi con la lingua" e lo consegno.

### Ordine che consiglio
  1. I 3 task infra (solo tuoi: HISTATS, social, SMTP)
  2. seo_head su index.php + browse.php  (~1h, il piu' redditizio)
  3. Selettore lingua in alto            (~1h)
  4. Pulizia sitemap.xml + robots.txt    (~15 min)
Il colore del carattere non e' in questa lista: e' gia' a norma.

===========================================================
FILE IN QUESTO ZIP (1)
===========================================================
allonwheel_style.css   (box di ricerca: geometria deterministica)

## Come verificare
Carica il CSS e apri una pagina qualsiasi (es. browse.php): il campo Search
deve essere alto ~38px con il pulsante rosso tondo INTERAMENTE dentro, a
filo destro, centrato verticalmente. Il testo digitato non deve mai finire
sotto l'icona. Restringi la finestra sotto gli 860px: il campo deve andare
a larghezza piena senza rompersi.
Ricorda: restano da applicare anche i pacchetti precedenti non confermati
(redirect+cancellazione, campi+4problemi).
