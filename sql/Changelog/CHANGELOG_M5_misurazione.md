# Allonwheel - M5: Misurazione (dashboard KPI) - 2026-07-06

Quinto e ULTIMO blocco del piano v1.1. Un solo ZIP. CRLF. PHP 8.3 lint OK.
Con questo si chiudono i 5 miglioramenti pre-lancio: si passa al GATE.

## Novita': `_admin/kpi.php` (+ voce "KPI" nella nav admin)
Dashboard con SOLI dati reali dal DB (dir. 14), protetta da AdminAuth:

1. **Overview**: utenti registrati (e verificati), fornitori attivi (e
   founding), annunci approved totali, ricerche salvate attive, RFQ totali.
   Se ci sono lead aperti da 3+ giorni: warning rosso con link a Leads.
2. **Trend settimanale (8 settimane ISO)**: nuovi utenti / annunci approved /
   RFQ / ricerche salvate, per settimana. La riga completa prima del giorno 1
   e' la **baseline W0** del piano dei 30 giorni.
3. **Funnel RFQ** per status (all-time e ultimi 30 giorni) + **tasso di
   risposta fornitori** = (quoted+won+lost)/distribuiti. NOTA onesta: il
   TEMPO di risposta non e' ancora tracciato (manca un timestamp di cambio
   status) -> il copy "typical reply in X days" resta neutro finche' non
   esiste questa baseline, come previsto dal piano.
4. **RFQ per famiglia** (top 8).
5. **Sorgenti lead** da `source_page` (patch M3): da quale pagina nascono le
   RFQ. Se la patch non e' ancora eseguita, la sezione lo segnala invece di
   rompersi. In calce la **convenzione UTM** per ogni link in uscita:
   `?utm_source=<canale>&utm_medium=<mezzo>&utm_campaign=launch30`.

Robustezza: ogni query e' in try/catch con fallback a zero -> la dashboard
funziona anche se le patch M2/M3/M4 non sono ancora tutte applicate.

## File (2)
_admin/kpi.php (NUOVO) | _admin/admin_header.php (voce nav)

## I 5 miglioramenti sono COMPLETI - prossimo: GATE DI LANCIO
Dal piano v1.1, il go/no-go richiede (in gran parte lato tuo):
- SMTP autenticato attivo (SPF+DKIM+DMARC, mail-tester >=9/10) + env SMTP_*
- HISTATS_ID nel .env - URL social reali nel footer - CRON_KEY nel .env e
  crontab per cron/saved_search_alerts.php
- Sitemap inviata in Search Console - backup completo provato
- Contenuto: >=30 fornitori, >=60 annunci con foto reali (seeding concierge,
  focus race-trailer + hospitality) - consenso digest ok (fatto in M4)
- La DATA: dammi l'evento motorsport di riferimento e preparo countdown e
  contenuti a tema per la settimana W1.
