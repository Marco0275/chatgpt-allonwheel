# Allonwheel — La bozza dell'ospite diventa sua al login
17 lug 2026. Un solo ZIP. CRLF. PHP lint 794/794 OK.
Richiede: patch SQL ad_drafts + libs/ad_draft.class.php (pacchetti precedenti).

===========================================================
COSA FA
===========================================================
login.php, subito dopo l'autenticazione riuscita, rivendica la bozza
eventualmente compilata da ospite:

    $aow_dtok = AdDraft::currentToken(false);   // non ne crea una nuova
    if ($aow_dtok !== '') { AdDraft::claim($pdo, $aow_dtok, $user['id_user']); }

Da quel momento la bozza ha un proprietario e il token non serve piu'.
E' il secondo anello della catena: save (wizard) -> CLAIM (qui) -> travaso
-> delete.

Questo file NON e' il wizard: e' un intervento a basso rischio, e infatti
l'ho fatto adesso invece di rimandarlo.

===========================================================
LE QUATTRO ACCORTEZZE
===========================================================
1. currentToken(FALSE): non crea un token nuovo.
   Con true, ogni login di chiunque avrebbe piantato un cookie di bozza a
   gente che non ha mai aperto il wizard. Qui si legge e basta.
2. claim() assegna SOLO bozze senza proprietario (vincolo dentro la classe):
   un token riciclato o rubato non puo' appropriarsi della bozza di un
   altro utente.
3. Tutto dentro try/catch Throwable: se la tabella non c'e' ancora, o la
   classe manca, il LOGIN DEVE FUNZIONARE LO STESSO. Un problema sulle
   bozze non puo' impedire a un utente di entrare. Viene solo loggato.
4. Posizione verificata: dopo login_user() (serve l'utente autenticato) e
   PRIMA dell'header Location (dopo il redirect non verrebbe mai eseguito).
   Il claim legge il COOKIE, non la sessione: il session_regenerate_id()
   fatto da login_user() non lo tocca.
   Verificata anche la chiave: $user['id_user'], che e' quella selezionata
   dalla query di login (riga 97), non 'id'.

===========================================================
DOVE SIAMO CON IL PUNTO 2
===========================================================
  [x] ritorno al wizard dopo il login (con guardia open redirect)
  [x] decisione: bozza in DB, verifica email mantenuta
  [x] tabella ad_drafts
  [x] classe AdDraft
  [x] pulizia GDPR nel cron
  [x] claim al login            <- questo pacchetto
  [ ] save dal wizard           <- tocca il wizard
  [ ] travaso bozza -> annuncio <- tocca il wizard
  [ ] delete dopo il travaso

Restano solo i pezzi che toccano i file del wizard. Tutto il resto e'
pronto, verificato e gia' in produzione senza cambiare nulla di visibile:
finche' nessuno salva bozze, questo codice non fa nulla.

===========================================================
FILE IN QUESTO ZIP (1)
===========================================================
01_login/login.php   claim della bozza + ritorno post-login

## Come verificare
1. Login normale: deve funzionare esattamente come prima (nessuna bozza in
   giro -> il blocco non fa nulla).
2. Se la patch SQL ad_drafts NON e' applicata: il login deve funzionare
   comunque. Nell'error log compare "claim bozza: ..." e basta.
3. Prova completa possibile solo quando il wizard salvera' le bozze.
