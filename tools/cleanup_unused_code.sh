#!/usr/bin/env bash
D="$(cd "$(dirname "$0")" && pwd)"
if command -v python3 >/dev/null 2>&1; then exec python3 "$D/cleanup_unused_code.py" "$@"
elif command -v python >/dev/null 2>&1; then exec python "$D/cleanup_unused_code.py" "$@"
else echo "Python non trovato. Installa Python 3."; exit 1; fi
