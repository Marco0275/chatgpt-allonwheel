<?php
declare(strict_types=1);

/**
 * ------------------------------------------------------------
 * AllOnWheel AI Studio
 * Bootstrap
 * ------------------------------------------------------------
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';

$id_user = require_user_logged_in();

require_once __DIR__ . '/../libs/user_tier.class.php';
require_once __DIR__ . '/../libs/plan_policy.class.php';

if (!PlanPolicy::canBlogPublish(UserTier::getTier($pdo, $id_user))) {

    http_response_code(403);

    exit('AI Studio is available for Gold users only.');

}

require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/../bootstrap/logger.php';
require_once __DIR__ . '/../bootstrap/cache.php';
require_once __DIR__ . '/../bootstrap/events.php';
require_once __DIR__ . '/../bootstrap/plugins.php';

$pageTitle = 'AllOnWheel AI Studio';