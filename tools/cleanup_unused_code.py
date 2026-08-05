#!/usr/bin/env python3
# ============================================================
# tools/cleanup_unused_code.py  (Windows/Linux/macOS)
# Elimina i file PHP/HTML orfani elencati in tools/orphan_php.txt.
# DEFAULT = dry-run (solo elenco). Cancellazione con --delete (o /DELETE),
# previa conferma. Si auto-posiziona nella RADICE del sito (superiore a tools/).
# Gli stub di redirect 301 NON sono in lista (protetti dall'analizzatore).
# ============================================================
import os, sys

ROOT     = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
MANIFEST = os.path.join(ROOT, "tools", "orphan_php.txt")

def main():
    args   = [a.lower() for a in sys.argv[1:]]
    delete = ("--delete" in args) or ("/delete" in args)
    if not os.path.isfile(MANIFEST):
        print("Manifest non trovato:", MANIFEST)
        print("Esegui prima:  python tools/analyze_unused.py")
        return 1

    present = []
    print("File candidati alla rimozione:")
    with open(MANIFEST, encoding="utf-8") as fh:
        for line in fh:
            rel = line.strip().replace("\\", "/")
            if not rel:
                continue
            path = os.path.join(ROOT, *rel.split("/"))
            if os.path.isfile(path):
                print("   " + rel)
                present.append(path)
            else:
                print("   [assente] " + rel)
    print("Totale presenti: %d" % len(present))

    if not delete:
        print("\nDRY-RUN: nessun file eliminato. Per cancellare davvero:")
        print("   python tools/cleanup_unused_code.py --delete")
        return 0

    if not present:
        print("Niente da eliminare.")
        return 0
    resp = input("\nEliminare DEFINITIVAMENTE questi %d file? (y/N) " % len(present)).strip().lower()
    if resp != "y":
        print("Annullato.")
        return 0
    n = 0
    for p in present:
        try:
            os.remove(p); n += 1
        except OSError as e:
            print("Errore su %s: %s" % (p, e))
    print("Eliminati %d file." % n)
    return 0

if __name__ == "__main__":
    sys.exit(main())
