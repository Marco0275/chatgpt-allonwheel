# Allonwheel — Guida di benvenuto all'account (email completata)
20 lug 2026. Un solo ZIP. CRLF. PHP lint 716/716 OK.

===========================================================
LA TUA DOMANDA: "l'utente riceve una guida alle opzioni del suo account?"
===========================================================
RISPOSTA: si', gia' esisteva - e in DUE punti (la V3.1 li ha introdotti):

 1. EMAIL alla verifica dell'account (01_login/verify.php): all'attivazione
    parte "Welcome to All on Wheel - your account is active".
 2. PANNELLO in dashboard (01_login/my_posts.php): un box "Welcome! Getting
    started" per i nuovi utenti, che sparisce dopo il primo annuncio e
    rimanda alle opzioni nella sidebar "My account".

Quindi la funzione c'era. Il problema: l'EMAIL era incompleta rispetto a
tutto cio' che l'account puo' fare.

===========================================================
COSA HO FATTO
===========================================================
L'email di benvenuto copriva solo 3 azioni (annuncio, azienda, preventivo).
L'account ne offre molte di piu'. Ho verificato le opzioni REALI leggendole
da sidebar_user_box.php (il box "My account") e ho riscritto l'email come
guida completa, organizzata per intento:

 - Sell a vehicle: annuncio free / premium (+ upgrade)
 - Buy a vehicle: request a quotation / post a wanted request (+ gestione)
 - Get found as a supplier: registra l'azienda in directory
 - Manage your account: My posts / Seller dashboard / profilo / impostazioni

Ora l'email e il pannello in dashboard raccontano la stessa cosa: l'utente
ha la guida completa sia via email (che puo' ritrovare) sia in dashboard.

Tutti i 10 link puntano a pagine REALMENTE esistenti (verificato file per
file, dir. 14: niente feature inventate). Se domani sposti o rinomini una di
quelle pagine, questa email va aggiornata.

===========================================================
NON HO TOCCATO
===========================================================
Il pannello in dashboard (my_posts.php) e' gia' fatto e va bene: 5 opzioni,
sparisce dopo il primo annuncio, con le chiavi i18n guide.*. Non l'ho
duplicato. Se vuoi renderlo completo quanto l'email (aggiungere wanted-manage,
seller dashboard, settings), e' una modifica a parte: dimmelo.

Nota: il pannello guida usa te('guide.*') con fallback inglese. Se le chiavi
guide.* non sono ancora nei file lingua IT/FR/DE, la dashboard mostra
l'inglese anche in quelle lingue. Posso aggiungerle se vuoi la guida
tradotta - dimmelo e in un colpo allineo email + pannello + traduzioni.

===========================================================
FILE IN QUESTO ZIP (1)
===========================================================
01_login/verify.php   email di benvenuto -> guida completa all'account

## Come verificare
1. Registra un nuovo utente e verifica l'email.
2. La mail "Welcome to All on Wheel" ora elenca TUTTE le sezioni (vendere,
   comprare, farsi trovare, gestire l'account) con link diretti.
3. Ogni link deve aprire la pagina giusta senza 404.
