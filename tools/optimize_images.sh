#!/usr/bin/env bash
# ============================================================
# tools/optimize_images.sh — genera le varianti WebP degli asset pesanti.
#
# Perche': le immagini del sito sono PNG da 2-2,6 MB (hero della home
# 2,6 MB, images/special_vehicle.png 2,3 MB). Su una connessione mobile
# la sola apertura della homepage scarica diversi megabyte prima di
# mostrare qualcosa. La stessa immagine in WebP pesa in genere l'85-90%
# in meno a parita' di resa visiva.
#
# Come viene usata la variante: aow_picture()/aow_css_bg() in
# includes/aow_media.php cercano il file .webp accanto all'originale e lo
# usano SOLO se esiste. Nessun file viene sostituito o cancellato: se lo
# script non viene mai eseguito, il sito continua a servire i PNG.
#
# Uso:
#   bash tools/optimize_images.sh              # tutto il sito
#   bash tools/optimize_images.sh images/hero  # solo una cartella
#
# Richiede cwebp (pacchetto webp):  sudo apt-get install webp
# ============================================================
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TARGET="${1:-.}"
QUALITY="${WEBP_QUALITY:-82}"
MIN_BYTES="${MIN_BYTES:-102400}"   # sotto i 100 KB la conversione non ripaga

if ! command -v cwebp >/dev/null 2>&1; then
  echo "cwebp non trovato. Installa il pacchetto 'webp' (es. sudo apt-get install webp)." >&2
  exit 1
fi

cd "$ROOT"
converted=0
saved=0

while IFS= read -r -d '' img; do
  case "$img" in
    ./vendor/*|./node_modules/*|*/thumbnail/*) continue ;;
  esac
  webp="${img%.*}.webp"
  # Rigenera solo se manca o se l'originale e' piu' recente.
  if [ -f "$webp" ] && [ "$webp" -nt "$img" ]; then continue; fi
  before=$(stat -c%s "$img")
  cwebp -quiet -q "$QUALITY" -m 6 "$img" -o "$webp"
  after=$(stat -c%s "$webp")
  # Se la webp non e' piu' leggera, si butta: non ha senso servirla.
  if [ "$after" -ge "$before" ]; then rm -f "$webp"; continue; fi
  converted=$((converted + 1))
  saved=$((saved + before - after))
  printf '%-60s %6s KB -> %6s KB\n' "${img#./}" "$((before / 1024))" "$((after / 1024))"
done < <(find "$TARGET" -type f -size +"$((MIN_BYTES / 1024))"k \
           \( -iname '*.png' -o -iname '*.jpg' -o -iname '*.jpeg' \) -print0)

echo
echo "Immagini convertite: $converted — risparmio totale: $((saved / 1024)) KB"
