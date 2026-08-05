<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

$config = require __DIR__ . '/../config/ai.php';

$url = $config['endpoint'] . '/models?key=' . urlencode($config['api_key']);

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);

if ($response === false) {
    die(curl_error($ch));
}

curl_close($ch);

$data = json_decode($response, true);

echo "<pre>";

foreach ($data['models'] ?? [] as $model) {

    echo $model['name'];

    if (isset($model['displayName'])) {
        echo "   --->   " . $model['displayName'];
    }

    echo PHP_EOL;
}

echo "</pre>";