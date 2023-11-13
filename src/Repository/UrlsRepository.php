<?php

namespace App\Repository;

use App\Models\Url;
use App\Connection;

class UrlsRepository
{
    private \PDO $pdo;

    public function __construct(Connection $pdo)
    {
        $this->pdo = $pdo->connect();
    }

    public function all()
    {
        $sql = "SELECT * FROM urls ORDER BY id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $urls = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(function ($url) {
            return new Url($url);
        }, $urls);
    }

    public function findOneById(int $id): ?Url
    {
        $sql = "SELECT * FROM urls WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue('id', $id);
        $stmt->execute();
        $url = $stmt->fetch(\PDO::FETCH_ASSOC);
        return is_array($url) ? new Url($url) : null;
    }

    public function findOneByName(string $name): ?Url
    {
        $sql = "SELECT * FROM urls WHERE name = :name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue('name', $name);
        $stmt->execute();
        $url = $stmt->fetch(\PDO::FETCH_ASSOC);
        return is_array($url) ? new Url($url) : null;
    }

    public function save(Url $url): string
    {
        $sql = "INSERT INTO urls (name, created_at) VALUES (:name, :created_at) RETURNING id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue('name', $url->getName());
        $stmt->bindValue('created_at', $url->getCreatedAt());
        $stmt->execute();
        [$id] = $stmt->fetch(\PDO::FETCH_NUM);
        return $id;
    }
}
