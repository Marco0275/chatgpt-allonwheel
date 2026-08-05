@echo off
REM Lanciatore Windows: usa l'implementazione Python. Uso: tools\cleanup_unused_code.bat [/DELETE]
where python >nul 2>&1 && ( python "%~dp0cleanup_unused_code.py" %* & goto :eof )
where py >nul 2>&1 && ( py "%~dp0cleanup_unused_code.py" %* & goto :eof )
echo Python non trovato nel PATH. Installa Python 3 (o usa cleanup_unused_code.sh su Linux/Mac).
