@echo off
setlocal enableextensions
REM ============================================================
REM cleanup_allonwheel.bat  --  Pulizia residui Dreamweaver (_notes)
REM
REM Esegui QUESTO file dalla cartella RADICE del progetto Allonwheel
REM (quella che contiene index.php e la cartella 07_rent).
REM
REM Elimina ricorsivamente:
REM   - tutte le cartelle "_notes"  (~127, residui Dreamweaver)
REM   - tutti i file "dwsync.xml"
REM
REM NON tocca maschera\ ne libs\mpdf\ : quelle vanno eliminate A MANO
REM (sono grandi e conviene verificarle prima -- vedi istruzioni).
REM ============================================================

cd /d "%~dp0"

REM --- Controllo di sicurezza: siamo nella radice di Allonwheel? ---
if not exist "index.php" goto :notroot
if not exist "07_rent\" goto :notroot

echo.
echo Cartella di lavoro: %CD%
echo.
echo Questo script eliminera' RICORSIVAMENTE:
echo    - tutte le cartelle "_notes" (residui Dreamweaver)
echo    - tutti i file "dwsync.xml"
echo.
echo NON verranno toccate  maschera\  ne  libs\mpdf\  (eliminale a mano).
echo.
set "CONF="
set /p CONF="Scrivi SI e premi Invio per procedere: "
if /i not "%CONF%"=="SI" goto :aborted

echo.
echo Elimino le cartelle _notes ...
for /d /r %%d in (_notes) do (
    if exist "%%d" (
        echo    rimuovo "%%d"
        rd /s /q "%%d"
    )
)

echo Elimino eventuali dwsync.xml residui ...
del /s /q "dwsync.xml" >nul 2>&1

echo.
echo Fatto. Cartelle _notes e dwsync.xml rimossi.
echo Ricorda: elimina a mano  maschera\  e  libs\mpdf\  (vedi istruzioni).
goto :end

:notroot
echo ERRORE: esegui questo .bat dalla cartella RADICE di Allonwheel
echo (quella che contiene index.php e la cartella 07_rent).
goto :end

:aborted
echo Operazione annullata. Nessun file eliminato.
goto :end

:end
echo.
pause
endlocal
