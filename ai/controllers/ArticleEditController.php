<?php
declare(strict_types=1);

require_once __DIR__ . '/../../repositories/ArticleRepository.php';

class ArticleEditController
{
    private ArticleRepository $repository;

    public function __construct(PDO $db)
    {
        $this->repository = new ArticleRepository($db);
    }

    public function load(int $id): Article
    {
        return $this->repository->findById($id);
    }
}