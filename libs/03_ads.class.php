<?php
/**
 * libs/03_ads.class.php — wrapper di retro-compatibilità.
 *
 * La classe SmartImage e' stata consolidata in libs/smart_image.class.php
 * per evitare la doppia dichiarazione (vedi piano Fase 4.2).
 * Questo file resta come alias storico: ogni require/include esistente
 * continua a funzionare, ma la definizione e' unica.
 */
require_once __DIR__ . '/smart_image.class.php';
