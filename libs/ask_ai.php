
// Codice da inserire melle pagine per richiamare AI

use Libs\AIManager;

$ai = new AIManager();

// Esegui la richiesta (il modello di default o quello che preferisci tra i disponibili)
$rawResponse = $ai->prompt("Genera un breve slogan per un e-commerce di ruote e bici.", "gemini-2.5-flash");

// Estrai il testo pulito
$testo = $ai->getResponseText($rawResponse);

echo $testo;