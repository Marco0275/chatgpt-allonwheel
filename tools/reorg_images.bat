@echo off
REM Lanciatore Windows: usa l'implementazione Python (robusta, cross-platform).
REM Si posiziona da solo nella radice del sito. Uso: tools\reorg_images.bat [/DRYRUN]
where python >nul 2>&1 && ( python "%~dp0reorg_images.py" %* & goto :eof )
where py >nul 2>&1 && ( py "%~dp0reorg_images.py" %* & goto :eof )
echo Python non trovato nel PATH. Installa Python 3 (o usa reorg_images.sh su Linux/Mac).
