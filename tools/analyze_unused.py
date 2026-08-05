#!/usr/bin/env python3
# ============================================================
# tools/analyze_unused.py
# Analizza l'uso di immagini e file PHP nel sito e genera:
#   - tools/unused_images.txt : immagini in images/ NON referenziate dal codice
#                               vivo (candidate a images/not_used)
#   - tools/orphan_php.txt     : file .php/.html non linkati e non in cartelle
#                               infrastrutturali (candidati a rimozione)
#   - tools/asset_usage_report.txt : report leggibile (usate/non usate + warning)
#
# Regole (conservative, per NON rompere il sito):
#   * "codice vivo" = .php .css .js .html/.htm (ESCLUSI dump .sql, moduli legacy
#     pirobox.*, readme .txt): un asset e' "usato" se il suo BASENAME compare li'.
#   * match a TOKEN INTERO (case-insensitive): "01.jpg" NON matcha dentro
#     "photo_..._01.jpg", ma matcha in "images/gallery/01.jpg".
#   * upload_image/ e images/ non sono scansionati come "codice".
#   * images/brand/ viene SEMPRE tenuto (loghi), anche se non referenziato.
#   * PHP in config/ libs/ shared/ includes/ _admin/ cron/ api/ lang/ e nelle
#     sezioni 0X_* sono infrastrutturali -> mai marcati orfani.
#
# Eseguire dalla RADICE del sito:  python3 tools/analyze_unused.py
# ============================================================
import os, re, sys

ROOT = "."
LIVE_EXT = (".php", ".css", ".js", ".html", ".htm")
IMG_EXT  = (".png", ".jpg", ".jpeg", ".gif", ".webp", ".svg", ".ico", ".bmp")
EXCLUDE_FROM_CODE = {"pirobox.module", "pirobox.theme.inc", "pirobox.admin.inc"}
PROTECTED_DIRS = {"config","libs","shared","includes","_admin","cron","api","lang",
                  "cookie_banner","css_pirobox","scripts","tools","fonts",
                  "01_login","02_free_ads","03_ads","04_request_offer","05_wanted",
                  "06_company","07_rent"}
ALWAYS_KEEP_PHP = {"index.php","header.php","footer.php","include_sidebar.php",
                   "template_no_sidebar.php","template_with_sidebar.php"}
KEEP_IMG_PREFIX = (os.path.join("images","brand"),)

def read_live_blob():
    parts = []
    for dp, dn, fn in os.walk(ROOT):
        segs = dp.split(os.sep)
        if "upload_image" in segs or ".git" in segs or "images" in segs:
            dn[:] = [d for d in dn if d not in ("upload_image", ".git", "images")]
        if "upload_image" in segs or ".git" in segs or "images" in segs:
            continue
        for f in fn:
            if f in EXCLUDE_FROM_CODE:
                continue
            if f.lower().endswith(LIVE_EXT):
                try:
                    parts.append(open(os.path.join(dp, f), encoding="utf-8", errors="ignore").read())
                except Exception:
                    pass
    return "\n".join(parts).lower()

def referenced(base, blob):
    b = re.escape(base.lower())
    return re.search(r'(?<![\w-])' + b + r'(?![\w-])', blob) is not None

def main():
    blob = read_live_blob()

    # ---- immagini ----
    imgs = []
    for dp, dn, fn in os.walk("images"):
        if "_notes" in dp.split(os.sep):
            continue
        for f in fn:
            if f.lower().endswith(IMG_EXT):
                imgs.append(os.path.normpath(os.path.join(dp, f)))
    used, unused = [], []
    for rel in imgs:
        (used if referenced(os.path.basename(rel), blob) else unused).append(rel)
    move = sorted(u for u in unused if not any(u.startswith(p) for p in KEEP_IMG_PREFIX))
    kept_brand = sorted(u for u in unused if any(u.startswith(p) for p in KEEP_IMG_PREFIX))

    # ---- PHP orfani ----
    php = []
    for dp, dn, fn in os.walk(ROOT):
        segs = dp.split(os.sep)
        if "upload_image" in segs or ".git" in segs:
            dn[:] = [d for d in dn if d not in ("upload_image", ".git")]
            continue
        for f in fn:
            if f.lower().endswith((".php", ".html", ".htm")):
                php.append(os.path.normpath(os.path.join(dp, f)))
    def is_redirect_stub(path):
        # Gli stub 301 (es. ads.php, catalog.php) servono a NON rompere vecchi
        # link/bookmark esterni: non sono orfani da cancellare, vanno tenuti.
        try:
            txt = open(path, encoding="utf-8", errors="ignore").read().lower()
        except Exception:
            return False
        return ("header(" in txt and "location" in txt and "301" in txt)

    orphans = []
    for p in php:
        segs = set(p.split(os.sep))
        base = os.path.basename(p)
        if segs & PROTECTED_DIRS or base in ALWAYS_KEEP_PHP:
            continue
        if referenced(base, blob):
            continue
        if is_redirect_stub(p):
            continue
        orphans.append(p)
    orphans = sorted(orphans)

    os.makedirs("tools", exist_ok=True)
    open("tools/unused_images.txt", "w", encoding="utf-8").write("\n".join(move) + ("\n" if move else ""))
    open("tools/orphan_php.txt", "w", encoding="utf-8").write("\n".join(orphans) + ("\n" if orphans else ""))

    with open("tools/asset_usage_report.txt", "w", encoding="utf-8") as r:
        r.write("ALLONWHEEL — REPORT USO ASSET\n")
        r.write("Immagini totali: %d | usate: %d | da spostare: %d | brand tenuti: %d\n"
                % (len(imgs), len(used), len(move), len(kept_brand)))
        r.write("PHP/HTML totali: %d | orfani candidati: %d\n\n" % (len(php), len(orphans)))
        r.write("=== IMMAGINI DA SPOSTARE in images/not_used ===\n")
        r.write("\n".join(move) + "\n\n")
        r.write("=== brand/ NON referenziati ma TENUTI (loghi) ===\n")
        r.write("\n".join(kept_brand) + "\n\n")
        r.write("=== PHP/HTML ORFANI ===\n")
        r.write("\n".join(orphans) + "\n")

    print("Immagini: %d totali, %d usate, %d da spostare (brand tenuti: %d)"
          % (len(imgs), len(used), len(move), len(kept_brand)))
    print("PHP/HTML: %d totali, %d orfani -> %s" % (len(php), len(orphans), ", ".join(orphans) or "(nessuno)"))
    print("Scritti: tools/unused_images.txt, tools/orphan_php.txt, tools/asset_usage_report.txt")

if __name__ == "__main__":
    main()
