<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

echo "CHECK 1: Inizio test<br>";

if (!file_exists(__DIR__ . '/../config/bootstrap.php')) {
    die("ERRORE: Il file bootstrap.php non esiste in questo percorso!");
}
require_once __DIR__ . '/../config/bootstrap.php';
echo "CHECK 2: Bootstrap caricato con successo<br>";

if (!file_exists(__DIR__ . '/../libs/AIManager.php')) {
    die("ERRORE: Il file AIManager.php non esiste in questo percorso!");
}
require_once __DIR__ . '/../libs/AIManager.php';
echo "CHECK 3: AIManager incluso con successo<br>";

try {
    echo "CHECK 4: Tento di istanziare AIManager...<br>";
    $ai = new \Libs\AIManager();

    echo "CHECK 5: Invio richiesta a Gemini...<br>";
    $response = $ai->prompt(
        "Rispondi esclusivamente con la parola OK."
    );

    echo "CHECK 6: Risposta ricevuta!<br><pre>";
    print_r($response);
    echo "</pre>";

} catch (Throwable $e) {
    echo "<h2>ERRORE INTERCETTATO</h2>";
    echo $e->getMessage();
}