<?php
ob_implicit_flush(true);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

echo "1. Inclusione AIManager...<br>";
require_once __DIR__ . '/../libs/AIManager.php';

echo "2. Tento l'istanziazione di AIManager...<br>";

try {
    // Se la classe ha un namespace (es. Libs), usa \Libs\AIManager()
    // Altrimenti usa new AIManager()
    $ai = new \Libs\AIManager(); 
    echo "3. Istanziazione completata!<br>";

    echo "4. Chiamo il metodo prompt()...<br>";
$response = $ai->prompt("Rispondi esclusivamente OK.", "gemini-flash-latest");

echo "5. Risposta ricevuta:<br><pre>";
    var_dump($response);
    echo "</pre>";

} catch (Throwable $e) {
    echo "<h2 style='color:red;'>ERRORE INTERCETTATO:</h2>";
    echo $e->getMessage();
}