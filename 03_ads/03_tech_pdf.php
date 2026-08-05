<?php
// 03_ads/03_tech_pdf.php  Retro-compatibilita': l'implementazione e' ora in
// shared/ad_pdf.php (gestisce FREE e PREMIUM). Qui si forza la tabella premium
// e si delega, cosi' eventuali vecchi link continuano a funzionare.
$_GET['t'] = '03_ads';
require __DIR__ . '/../shared/ad_pdf.php';
