<?php
declare(strict_types=1);

require_once __DIR__.'/../libs/AIResponseParser.php';

$response = <<<TXT
```json
{
    "title":"Example title",
    "slug":"example-title",
    "excerpt":"Example excerpt",
    "meta_title":"Example meta",
    "meta_description":"Example description",
    "content":"Example content",
    "faq":[
        {
            "question":"Question 1",
            "answer":"Answer 1"
        }
    ]
}
TXT;

try
{
	$article = AIResponseParser::parse($response);

	echo "<pre>";

	print_r($article);

	echo "</pre>";
}
catch(Throwable $e)
{
	echo $e->getMessage();
}
