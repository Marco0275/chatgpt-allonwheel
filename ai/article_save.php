<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../config/csrf.php';

require_once __DIR__ . '/../repositories/ArticleRepository.php';

require_user_logged_in();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Location: articles.php');
    exit;

}

csrf_verify();

$repository = new ArticleRepository($pdo);

$id = (int)($_POST['id'] ?? 0);

$article = $repository->findById($id);

if ($article === null) {

    $_SESSION['ai_error'] = 'Article not found.';

    header('Location: articles.php');

    exit;

}

$article->setTitle(
    trim($_POST['title'] ?? '')
);

$article->setSlug(
    trim($_POST['slug'] ?? '')
);

$article->setExcerpt(
    trim($_POST['excerpt'] ?? '')
);

$article->setBody(
    trim($_POST['body'] ?? '')
);

$article->setCategory(
    trim($_POST['category'] ?? '')
);

$article->setLanguage(
    trim($_POST['language'] ?? 'en')
);

$article->setSeoTitle(
    trim($_POST['seo_title'] ?? '')
);

$article->setSeoDescription(
    trim($_POST['seo_description'] ?? '')
);

$article->setCanonical(
    trim($_POST['canonical'] ?? '')
);

$repository->save($article);

$_SESSION['ai_success'] =
    'Article saved successfully.';

header(

    'Location: article_edit.php?id=' .
    $article->getId()

);

exit;