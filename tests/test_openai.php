<?php
declare(strict_types=1);

use Libs\AIManager;

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../libs/AIManager.php';

$ai = new AIManager();

try {

    $response = $ai->prompt(
        "Rispondi solamente con la parola OK"
    );

    echo "<pre>";

    print_r($response);

    echo "</pre>";

} catch(Throwable $e){

    echo "<h2>ERRORE</h2>";

    echo $e->getMessage();

}