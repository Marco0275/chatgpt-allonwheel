<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Application
|--------------------------------------------------------------------------
*/

define('APP_NAME', 'AllOnWheel AI Studio');
define('APP_VERSION', '1.1.0');
define('APP_ENV', 'production');
define('APP_DEBUG', false);
define('APP_TIMEZONE', 'Europe/Rome');

date_default_timezone_set(APP_TIMEZONE);

/*
|--------------------------------------------------------------------------
| AI Providers
|--------------------------------------------------------------------------
*/

define('DEFAULT_AI_PROVIDER', 'openai');
define('AI_PROVIDER_FALLBACK', 'gemini');
define('AI_PROVIDER_AUTO_FAILOVER', true);

/*
|--------------------------------------------------------------------------
| OpenAI
|--------------------------------------------------------------------------
*/

define('OPENAI_ENABLED', true);
define('OPENAI_API_KEY', '');
define('OPENAI_MODEL', 'gpt-5.5');
define('OPENAI_TIMEOUT', 180);
define('OPENAI_MAX_RETRIES', 3);
define('OPENAI_TEMPERATURE', 0.7);
define('OPENAI_MAX_COMPLETION_TOKENS', 4096);

/*
|--------------------------------------------------------------------------
| Google Gemini
|--------------------------------------------------------------------------
*/

define('GOOGLE_PROVIDER_ENABLED', true);
define('GEMINI_API_KEY', '');
define('GEMINI_MODEL', 'gemini-2.5-pro');
define('GEMINI_TIMEOUT', 180);
define('GEMINI_MAX_RETRIES', 3);

/*
|--------------------------------------------------------------------------
| Anthropic
|--------------------------------------------------------------------------
*/

define('ANTHROPIC_PROVIDER_ENABLED', false);
define('ANTHROPIC_API_KEY', '');
define('ANTHROPIC_MODEL', 'claude-sonnet-4');

/*
|--------------------------------------------------------------------------
| Mistral
|--------------------------------------------------------------------------
*/

define('MISTRAL_PROVIDER_ENABLED', false);
define('MISTRAL_API_KEY', '');
define('MISTRAL_MODEL', 'mistral-large');

/*
|--------------------------------------------------------------------------
| Ollama
|--------------------------------------------------------------------------
*/

define('OLLAMA_PROVIDER_ENABLED', false);
define('OLLAMA_ENDPOINT', 'http://127.0.0.1:11434');
define('OLLAMA_MODEL', 'llama3');

/*
|--------------------------------------------------------------------------
| Queue
|--------------------------------------------------------------------------
*/

define('QUEUE_ENABLED', true);
define('QUEUE_CONCURRENCY', 3);
define('QUEUE_SLEEP', 5);
define('QUEUE_MAX_RUNTIME', 3600);
define('QUEUE_MAX_RETRIES', 5);

/*
|--------------------------------------------------------------------------
| Workflow
|--------------------------------------------------------------------------
*/

define('WORKFLOW_AUTOSTART', true);
define('WORKFLOW_AUTOPUBLISH', false);
define('WORKFLOW_AUTOSEO', true);
define('WORKFLOW_TRANSLATE', false);
define('WORKFLOW_GENERATE_FAQ', true);
define('WORKFLOW_GENERATE_TAGS', true);
define('WORKFLOW_GENERATE_SCHEMA', true);
define('WORKFLOW_GENERATE_IMAGES', true);
define('WORKFLOW_VALIDATE_OUTPUT', true);

/*
|--------------------------------------------------------------------------
| Editorial Calendar
|--------------------------------------------------------------------------
*/

define('EDITORIAL_IMPORT_ENABLED', true);
define('EDITORIAL_DEFAULT_STATUS', 'scheduled');
define('EDITORIAL_LOOKAHEAD_DAYS', 30);

/*
|--------------------------------------------------------------------------
| Content
|--------------------------------------------------------------------------
*/

define('ARTICLE_DEFAULT_LANGUAGE', 'English');
define('ARTICLE_DEFAULT_TONE', 'Professional');
define('ARTICLE_DEFAULT_LENGTH', 'Medium');
define('ARTICLE_MIN_WORDS', 600);
define('ARTICLE_MAX_WORDS', 3000);

/*
|--------------------------------------------------------------------------
| SEO
|--------------------------------------------------------------------------
*/

define('SEO_AUTOMATIC_META_TITLE', true);
define('SEO_AUTOMATIC_META_DESCRIPTION', true);
define('SEO_AUTOMATIC_SLUG', true);
define('SEO_AUTOMATIC_SCHEMA', true);
define('SEO_AUTOMATIC_INTERNAL_LINKS', true);

/*
|--------------------------------------------------------------------------
| Images
|--------------------------------------------------------------------------
*/

define('AI_IMAGE_PROVIDER', 'openai');
define('AI_IMAGE_ENABLED', false);
define('AI_IMAGE_SIZE', '1536x1536');

/*
|--------------------------------------------------------------------------
| Cache
|--------------------------------------------------------------------------
*/

define('CACHE_ENABLED', true);
define('CACHE_TTL', 3600);

/*
|--------------------------------------------------------------------------
| Logging
|--------------------------------------------------------------------------
*/

define('LOG_LEVEL', 'info');
define('LOG_AI_REQUESTS', true);
define('LOG_AI_RESPONSES', true);
define('LOG_WORKFLOWS', true);
define('LOG_SQL', false);

/*
|--------------------------------------------------------------------------
| Security
|--------------------------------------------------------------------------
*/

define('ENABLE_RATE_LIMIT', true);
define('MAX_REQUESTS_PER_MINUTE', 30);

/*
|--------------------------------------------------------------------------
| Cost Control
|--------------------------------------------------------------------------
*/

define('DAILY_TOKEN_LIMIT', 1000000);
define('MONTHLY_TOKEN_LIMIT', 25000000);
define('STOP_ON_LIMIT', true);

/*
|--------------------------------------------------------------------------
| Feature Flags
|--------------------------------------------------------------------------
*/

define('FEATURE_REWRITE', true);
define('FEATURE_TRANSLATE', true);
define('FEATURE_SEO', true);
define('FEATURE_FAQ', true);
define('FEATURE_SCHEMA', true);
define('FEATURE_TAGS', true);
define('FEATURE_SOCIAL', true);
define('FEATURE_NEWSLETTER', true);
define('FEATURE_IMAGE_PROMPT', true);
define('FEATURE_AUTOPUBLISH', false);
define('FEATURE_EDITORIAL_CALENDAR', true);
define('FEATURE_AI_ANALYTICS', true);

/*
|--------------------------------------------------------------------------
| AI Studio
|--------------------------------------------------------------------------
*/

define('AI_STUDIO_NAME', 'AllOnWheel AI Studio');
define('AI_STUDIO_BUILD', '1.1');
define('AI_STUDIO_DATABASE_VERSION', '1.1');