#!/usr/bin/env python3
# ============================================================
# tools/reorg_images.py  (Windows/Linux/macOS)
# Sposta le immagini NON usate (tools/unused_images.txt) in images/not_used/,
# ricreando le sottocartelle. NON tocca upload_image/ ne' images/brand/.
# Reversibile (solo move). Si auto-posiziona: la RADICE del sito e' la cartella
# superiore a tools/, indipendentemente da dove lo lanci.
#
#   python tools/reorg_images.py            -> sposta
#   python tools/reorg_images.py --dry-run  -> elenca soltanto (anche /DRYRUN)
# ============================================================
import os, sys, shutil

ROOT     = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))  # parent di tools/
MANIFEST = os.path.join(ROOT, "tools", "unused_images.txt")
DESTREL  = os.path.join("images", "not_used")

def main():
    args = [a.lower() for a in sys.argv[1:]]
    dry  = ("--dry-run" in args) or ("/dryrun" in args)
    if not os.path.isfile(MANIFEST):
        print("Manifest non trovato:", MANIFEST)
        print("Esegui prima:  python tools/analyze_unused.py  (dalla radice del sito)")
        return 1

    moved = missing = skipped = 0
    with open(MANIFEST, encoding="utf-8") as fh:
        for line in fh:
            rel = line.strip().replace("\\", "/")
            if not rel:
                continue
            if not rel.startswith("images/") or rel.startswith("images/not_used/"):
                skipped += 1
                continue
            src = os.path.join(ROOT, *rel.split("/"))
            if not os.path.isfile(src):
                missing += 1
                continue
            dst = os.path.join(ROOT, DESTREL, *rel[len("images/"):].split("/"))
            if dry:
                print("DRY  %s  ->  %s" % (rel, os.path.relpath(dst, ROOT).replace("\\", "/")))
                continue
            os.makedirs(os.path.dirname(dst), exist_ok=True)
            shutil.move(src, dst)
            moved += 1

    if dry:
        print("Dry-run completato. (in lista: %d, mancanti su disco: %d)"
              % (sum(1 for _ in open(MANIFEST, encoding="utf-8") if _.strip()), missing))
    else:
        print("Spostate %d immagini in %s  (mancanti/gia' spostate: %d)." % (moved, DESTREL, missing))
        print("Per annullare: rispostare i file da %s/ sotto images/." % DESTREL)
    return 0

if __name__ == "__main__":
    sys.exit(main())
