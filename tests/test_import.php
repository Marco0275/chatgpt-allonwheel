<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../libs/EditorialQueue.php';
require_once __DIR__ . '/../libs/ExcelImporter.php';

$file = __DIR__ . '/../imports/editorial_plan.xlsx';

$importer = new ExcelImporter($db);

$total = $importer->import($file);

echo "<h2>Import completed</h2>";

echo "<strong>{$total}</strong> records imported.";