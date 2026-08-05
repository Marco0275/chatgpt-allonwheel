#!/usr/bin/env bash
# ============================================================================
#  aow_debug.sh — Audit di debug MASSIMO per Allonwheel (PHP 8.3 / MySQL)
#  Sola lettura: NON modifica nulla, produce solo un report ordinato.
#
#  USO:
#     bash aow_debug.sh /percorso/della/cartella-sito   > report.txt
#     # oppure, partendo da uno ZIP:
#     unzip -q sito.zip -d /tmp/sito && bash aow_debug.sh /tmp/sito
#
#  Copre le classi di bug realmente incontrate nel progetto:
#   1) Sintassi PHP (lint)          6) Credenziali/config esposti
#   2) include/require rotti        7) File pubblici pericolosi (dump .sql, .env)
#   3) Variabili usate prima        8) Riferimenti a file inesistenti (link/action)
#      della definizione            9) Funzioni JS chiamate ma non definite
#   4) Tabelle SQL hardcoded       10) CSS: parentesi graffe sbilanciate
#   5) SQL injection / XSS         11) Igiene: CRLF, TODO/FIXME, file orfani
#  Alla fine: riepilogo con conteggi e priorità.
# ============================================================================
set -uo pipefail
ROOT="${1:-.}"
[ -d "$ROOT" ] || { echo "ERRORE: cartella non trovata: $ROOT"; exit 1; }
cd "$ROOT" || exit 1

# Cartelle vendor da ignorare nelle analisi applicative
VEND='vendor|/mpdf|/PHPMailer|node_modules'
php_files() { find . -name '*.php' | grep -vE "$VEND"; }
SEP() { echo; echo "════════════════════════════════════════════════════════════"; echo " $1"; echo "════════════════════════════════════════════════════════════"; }
c_lint=0; c_inc=0; c_undef=0; c_hard=0; c_sql=0; c_xss=0; c_cred=0; c_dump=0; c_deadref=0; c_js=0; c_css=0

echo "############################################################"
echo "#  AOW DEBUG AUDIT  —  $(date '+%Y-%m-%d %H:%M')"
echo "#  Cartella: $(pwd)"
echo "#  File PHP applicativi: $(php_files | wc -l)  |  totali (incl. vendor): $(find . -name '*.php' | wc -l)"
echo "############################################################"

# ---------------------------------------------------------------------------
SEP "1) SINTASSI PHP (php -l) — errori BLOCCANTI"
if command -v php >/dev/null 2>&1; then
  while IFS= read -r f; do
    out=$(php -l "$f" 2>&1) || { echo "  ✗ $f"; echo "     $(echo "$out" | grep -i 'error' | head -1)"; c_lint=$((c_lint+1)); }
  done < <(php_files)
  [ "$c_lint" -eq 0 ] && echo "  ✓ Nessun errore di sintassi."
else
  echo "  ⚠ php non disponibile: salto il lint (installa php-cli per il check completo)."
fi

# ---------------------------------------------------------------------------
SEP "2) include/require verso file INESISTENTI"
while IFS= read -r f; do
  d=$(dirname "$f")
  grep -oE "(require|include)(_once)?[[:space:]]*\(?[[:space:]]*__DIR__[[:space:]]*\.[[:space:]]*'[^']+'" "$f" 2>/dev/null | \
  grep -oE "'[^']+'" | tr -d "'" | while IFS= read -r rel; do
    # concatena SENZA os.path.join (che tratterebbe '/../x' come assoluto)
    tgt=$(python3 -c "import os,sys; print(os.path.normpath(sys.argv[1].rstrip('/')+'/'+sys.argv[2].lstrip('/')))" "$d" "$rel" 2>/dev/null)
    # ignora falsi positivi in commenti di esempio
    case "$rel" in */includes/seo_head.php) continue;; esac
    if [ -n "$tgt" ] && [ ! -f "$tgt" ]; then echo "  ✗ $f  ->  $rel"; fi
  done
done < <(php_files) | sort -u | tee /tmp/_inc.txt
c_inc=$(wc -l < /tmp/_inc.txt)
[ "$c_inc" -eq 0 ] && echo "  ✓ Nessun include/require rotto."

# ---------------------------------------------------------------------------
SEP "3) VARIABILI usate PRIMA della definizione (classe di bug \$aow_tbl)"
# euristica: variabili $aow_* / $id_* usate in una query prima di essere assegnate
python3 - <<'PY' | tee /tmp/_undef.txt
import re, os
VEND=re.compile(r'vendor|/mpdf|/PHPMailer|node_modules')
for r,d,fs in os.walk('.'):
    if VEND.search(r): continue
    for f in fs:
        if not f.endswith('.php'): continue
        p=os.path.join(r,f)
        c=open(p,encoding='utf-8',errors='replace').read()
        # variabili interessanti (nome tabella dinamico, id)
        for var in set(re.findall(r'\$(aow_tbl|aow_lt|aow_max|id_ads|id_user)\b', c)):
            # prima assegnazione
            m_def=re.search(r'\$'+var+r'\s*=', c)
            # primo uso "significativo" (in stringa SQL o concatenazione)
            m_use=re.search(r'`\'\s*\.\s*\$'+var+r'|\$'+var+r'\s*\.\s*\'|FROM\s+`?\'\s*\.\s*\$'+var, c)
            if m_use and (not m_def or m_use.start() < m_def.start()):
                ln=c[:m_use.start()].count('\n')+1
                print(f"  ⚠ {p}:{ln}  ${var} usata prima/senza definizione")
PY
c_undef=$(grep -c '⚠' /tmp/_undef.txt 2>/dev/null || echo 0)
[ "$c_undef" -eq 0 ] && echo "  ✓ Nessun uso-prima-della-definizione rilevato (euristica)."

# ---------------------------------------------------------------------------
SEP "4) INCOERENZA tabella: file che usano \$aow_tbl MA anche una tabella hardcoded"
# Segnala solo i file DAVVERO incoerenti: usano il nome dinamico $aow_tbl in un punto
# e una tabella annunci scritta a mano in un altro (classe di bug 02_01_modify_upload).
: > /tmp/_hard.txt
for f in $(grep -rlE "\\\$aow_tbl" --include="*.php" 02_free_ads/ 03_ads/ 2>/dev/null); do
  hard=$(grep -nE "\`0[23]_(free_ads|ads)(_gallery)?\`" "$f" 2>/dev/null | grep -vE "// |/\*")
  if [ -n "$hard" ]; then
    echo "  ⚠ $f usa \$aow_tbl ma contiene anche tabelle hardcoded:" >> /tmp/_hard.txt
    echo "$hard" | sed 's/^/       /' >> /tmp/_hard.txt
  fi
done
cat /tmp/_hard.txt
[ -s /tmp/_hard.txt ] || echo "  ✓ Nessun file incoerente (dinamico + hardcoded insieme)."

# ---------------------------------------------------------------------------
SEP "5a) SQL INJECTION — query interpolate (escludo cast (int) sicuri)"
# Interpolazione di variabili in query(): sospetto SOLO se la variabile non è
# palesemente sanificata (cast (int), o whitelist tramite $aow_tbl/$table validati).
grep -rnE "(query|exec)\s*\(\s*[\"'].*\\\$[A-Za-z_]" --include="*.php" . 2>/dev/null | \
  grep -vE "$VEND|prepare|// |/\*|PDO::" | \
  grep -vE "\(int\)\s*\\\$|\(float\)\s*\\\$|\\\$aow_tbl|\{\\\$table\}" | \
  sed 's/^/  ⚠ /' | head -40 | tee /tmp/_sql.txt
[ -s /tmp/_sql.txt ] || echo "  ✓ Nessuna query interpolata rischiosa (cast/whitelist ovunque)."

SEP "5b) XSS — echo di variabili senza htmlspecialchars"
grep -rnE "echo\s+\\\$_(GET|POST|REQUEST|COOKIE)\[" --include="*.php" . 2>/dev/null | \
  grep -vE "$VEND|htmlspecialchars|htmlentities" | sed 's/^/  ⚠ /' | head -30 | tee /tmp/_xss.txt
c_xss=$(wc -l < /tmp/_xss.txt 2>/dev/null || echo 0)
[ "$c_xss" -eq 0 ] && echo "  ✓ Nessun echo diretto di input utente non escapato."

# ---------------------------------------------------------------------------
SEP "6) CREDENZIALI / CONFIG esposti (grave)"
# Segnala SOLO se il valore e' realmente presente (non placeholder vuoto,
# non redatto con ... o <...>): evita i falsi positivi su *.example e changelog.
: > /tmp/_cred.txt
for f in $(find . -maxdepth 3 -type f \( -name "*env*" -o -name "*.env" \) 2>/dev/null | grep -vE "$VEND|\.php$|\.example$"); do
  if grep -qE "^(DB_PASSWORD|SMTP_PASS|CRON_TOKEN|API_KEY|SECRET)=[^[:space:]]+" "$f" 2>/dev/null; then
    prot="NON protetto"
    [ -f "$(dirname "$f")/.htaccess" ] && prot="protetto da .htaccess (ma da rimuovere)"
    echo "  ✗ CREDENZIALI IN CHIARO: $f  [$prot]" >> /tmp/_cred.txt
  fi
done
if [ -d config ] && [ ! -f config/.htaccess ]; then
  echo "  ✗ config/ SENZA .htaccess: i file di config potrebbero essere serviti via web" >> /tmp/_cred.txt
fi
cat /tmp/_cred.txt
[ -s /tmp/_cred.txt ] || echo "  ✓ Nessuna credenziale reale esposta; config protetta."

# ---------------------------------------------------------------------------
SEP "7) FILE PUBBLICI PERICOLOSI nel webroot (dump DB, backup)"
find . -maxdepth 2 -type f \( -name "*.sql" -o -name "*.bak" -o -name "*.old" -o -name "*.zip" -o -name "*.tar*" \) 2>/dev/null | \
  grep -vE "$VEND|sql/Changelog" | sed 's/^/  ⚠ /' | tee /tmp/_dump.txt
c_dump=$(wc -l < /tmp/_dump.txt 2>/dev/null || echo 0)
[ "$c_dump" -eq 0 ] && echo "  ✓ Nessun dump/backup pubblico nel webroot."

# ---------------------------------------------------------------------------
SEP "8) RIFERIMENTI a file .php INESISTENTI (href/action)"
python3 - <<'PY' | sort -u | tee /tmp/_deadref.txt
import re, os
VEND=re.compile(r'vendor|/mpdf|/PHPMailer|node_modules')
exists=set()
for r,d,fs in os.walk('.'):
    for f in fs:
        if f.endswith('.php'): exists.add(f)
# NB: il nome file puo' contenere trattini (cookie-policy.php) e punti.
HREF=re.compile(r'(?:href|action)\s*=\s*[\'"]([^\'"]+)[\'"]', re.I)
for r,d,fs in os.walk('.'):
    if VEND.search(r): continue
    for f in fs:
        if not f.endswith('.php'): continue
        p=os.path.join(r,f)
        for i,ln in enumerate(open(p,encoding='utf-8',errors='replace'),1):
            for m in HREF.finditer(ln):
                val=m.group(1).strip()
                # salta URL esterni (es. facebook.com/profile.php), mailto, ancore, php dinamico
                if re.match(r'(https?:)?//|mailto:|tel:|#|javascript:', val): continue
                if '<?php' in val or '<?=' in val: continue
                # prendi il pezzo prima di ?query e #anchor, poi il basename
                path=val.split('?')[0].split('#')[0]
                base=os.path.basename(path)
                if not base.endswith('.php'): continue
                if base not in exists:
                    print(f"  \u26a0 {p}:{i} -> {base}  (href: {val[:60]})")
PY
c_deadref=$(grep -c '⚠' /tmp/_deadref.txt 2>/dev/null || echo 0)
[ "$c_deadref" -eq 0 ] && echo "  ✓ Nessun link/action verso file .php inesistenti."

# ---------------------------------------------------------------------------
SEP "9) FUNZIONI JS chiamate ma NON definite (anche <script> inline)"
python3 - <<'PY' | tee /tmp/_js.txt
import re, os
VEND=re.compile(r'vendor|node_modules|/mpdf|/PHPMailer')
# raccogli le funzioni definite sia nei .js SIA negli <script> inline dei .php/.html
defined=set()
for r,d,fs in os.walk('.'):
    if VEND.search(r): continue
    for f in fs:
        if f.endswith(('.js','.php','.html')):
            try: c=open(os.path.join(r,f),encoding='utf-8',errors='replace').read()
            except Exception: continue
            defined|=set(re.findall(r'function\s+([A-Za-z_]\w*)', c))
            defined|=set(re.findall(r'(?:var|let|const)?\s*([A-Za-z_]\w*)\s*=\s*function', c))
            defined|=set(re.findall(r'(?:var|let|const)\s+([A-Za-z_]\w*)\s*=\s*\([^)]*\)\s*=>', c))
used=[]
for r,d,fs in os.walk('.'):
    if VEND.search(r): continue
    for f in fs:
        if not f.endswith(('.php','.html')): continue
        p=os.path.join(r,f)
        for i,ln in enumerate(open(p,encoding='utf-8',errors='replace'),1):
            for m in re.finditer(r'on(?:submit|click|change)\s*=\s*[\'"][^\'"]*?(?:return\s+)?([A-Za-z_]\w*)\s*\(', ln):
                used.append((m.group(1), p, i))
for name,p,i in used:
    if name not in defined and name not in ('true','false','this','alert','confirm'):
        print(f"  \u26a0 {p}:{i}  {name}() chiamata ma NON definita (ne' .js ne' inline)")
PY
c_js=$(grep -c '⚠' /tmp/_js.txt 2>/dev/null || echo 0)
[ "$c_js" -eq 0 ] && echo "  ✓ Nessuna funzione JS chiamata-ma-non-definita."

# ---------------------------------------------------------------------------
SEP "10) CSS — parentesi graffe sbilanciate"
for css in $(find . -name "*.css" | grep -vE "$VEND"); do
  o=$(tr -cd '{' < "$css" | wc -c); cl=$(tr -cd '}' < "$css" | wc -c)
  if [ "$o" -ne "$cl" ]; then echo "  ✗ $css : { =$o } =$cl (SBILANCIATE)"; c_css=$((c_css+1)); fi
done
[ "$c_css" -eq 0 ] && echo "  ✓ Tutti i CSS hanno graffe bilanciate."

# ---------------------------------------------------------------------------
SEP "11) IGIENE (non bloccante): TODO/FIXME, file orfani, cartelle estranee"
echo "  · TODO/FIXME/XXX/HACK nei sorgenti:"
grep -rnE "TODO|FIXME|XXX|HACK" --include="*.php" . 2>/dev/null | grep -vE "$VEND" | wc -l | sed 's/^/     totale: /'
echo "  · Residui Dreamweaver (_notes / dwsync.xml):"
find . -type d -name "_notes" | grep -vE "$VEND" | wc -l | sed 's/^/     cartelle _notes: /'
echo "  · Cartelle sospette (fuori dallo schema del sito):"
ls -d */ 2>/dev/null | grep -vE "^(0[0-9]_|config|includes|js|css|libs|images|upload|shared|sql|cron|_admin|lang|fonts|report|scripts|vendor|css_pirobox)/" | sed 's/^/     /' || true
echo "  · File con newline UNIX dove il progetto usa CRLF (primi 10):"
grep -rLI $'\r' --include="*.php" . 2>/dev/null | grep -vE "$VEND" | head -10 | sed 's/^/     /' || true

# ---------------------------------------------------------------------------
SEP "RIEPILOGO — priorità di intervento"
# ricalcolo robusto dai file temporanei (i contatori dentro le pipe non propagano)
n() { if [ -f "$1" ]; then grep -cE '✗|⚠' "$1" 2>/dev/null | head -1 | tr -cd '0-9'; else printf 0; fi; }
c_inc=$(n /tmp/_inc.txt);   c_undef=$(n /tmp/_undef.txt); c_hard=$(n /tmp/_hard.txt)
c_sql=$(n /tmp/_sql.txt);   c_xss=$(n /tmp/_xss.txt);     c_dump=$(n /tmp/_dump.txt)
c_deadref=$(n /tmp/_deadref.txt); c_js=$(n /tmp/_js.txt); c_cred=$(n /tmp/_cred.txt)
: "${c_lint:=0}" "${c_cred:=0}" "${c_css:=0}"
crit=$((c_lint + c_cred + c_undef + c_css))
high=$((c_inc + c_hard + c_sql + c_xss + c_js))
med=$((c_dump + c_deadref))
echo "  🔴 CRITICI  (rompono o espongono): $crit"
echo "       lint=$c_lint  credenziali=$c_cred  var-undef=$c_undef  css-rotto=$c_css"
echo "  🟠 ALTI     (bug/sicurezza):       $high"
echo "       include-rotti=$c_inc  tabelle-hardcoded=$c_hard  sql-inj=$c_sql  xss=$c_xss  js-mancanti=$c_js"
echo "  🟡 MEDI     (igiene/robustezza):   $med"
echo "       dump-pubblici=$c_dump  link-morti=$c_deadref"
echo
echo "  Ordine consigliato: prima i 🔴, poi 🟠, poi 🟡."
echo "  (Report di sola lettura: nessun file è stato modificato.)"
