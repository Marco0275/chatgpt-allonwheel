<?php
declare(strict_types=1);

define('AI_CRON_LOCK','AI_CRON');

define('AI_MAX_EXECUTION_TIME',300);

define('AI_RETRY_DELAY',600);

define('AI_DEFAULT_AUTHOR',1);

define('AI_SOURCE','Gemini');

define('AI_PROVIDER','Google AI Studio');

$apiKey='';

if(isset($_ENV['GEMINI_API_KEY']))
{
    $apiKey=(string)$_ENV['GEMINI_API_KEY'];
}
else
{
    $env=getenv('GEMINI_API_KEY');

    if($env!==false)
    {
        $apiKey=(string)$env;
    }
}

return [

    'provider'=>'gemini',

    'model' => 'gemini-flash-latest',

    'endpoint'=>'https://generativelanguage.googleapis.com/v1beta',

    'timeout'=>60,

    'api_key'=>$apiKey

];

define('AI_DEFAULT_LANGUAGE','EN');

define('AI_TRANSLATIONS',[
    'IT',
    'FR',
    'DE'
]);

define('AI_STATUS_DRAFT','scheduled');

define('AI_AUTHOR_ID',1);

define('AI_TIMEOUT',120);

define('AI_MAX_RETRY',3);

define('AI_TEMPERATURE',0.7);

define('AI_MAX_TOKENS',12000);