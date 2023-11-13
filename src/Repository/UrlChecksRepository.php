<?php

namespace App\Repository;

use App\Models\UrlCheck;
use App\Connection;

class UrlChecksRepository
{
    private \PDO $pdo;

    public function __construct(Connection $pdo)
    {
        $this->pdo = $pdo->connect();
    }

    public function all()
    {
        $sql = "SELECT * FROM url_checks ORDER BY id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $checks = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(function ($check) {
            return new UrlCheck($check);
        }, $checks);
    }

    public function findAllByUrlId(int $urlId)
    {
        $sql = "SELECT * FROM url_checks WHERE url_id = ? ORDER BY id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(1, $urlId, \PDO::PARAM_INT);
        $stmt->execute();
        $checks = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(function ($check) {
            return new UrlCheck($check);
        }, $checks);
    }

    public function findLastByUrlId(int $urlId)
    {
        $sql = "SELECT * FROM url_checks WHERE url_id = ? ORDER BY id DESC LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(1, $urlId, \PDO::PARAM_INT);
        $stmt->execute();
        $check = $stmt->fetch(\PDO::FETCH_ASSOC);
        return is_array($check) ? new UrlCheck($check) : null;
    }

    public function save(UrlCheck $urlCheck): void
    {
        $columns = "url_id, status_code, h1, title, description, created_at";
        $values = "?, ?, ?, ?, ?, ?";
        $sql = "INSERT INTO url_checks ({$columns}) VALUES ({$values}) RETURNING id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(1, $urlCheck->getUrlId());
        $stmt->bindValue(2, $urlCheck->getStatusCode());
        $stmt->bindValue(3, $urlCheck->getH1());
        $stmt->bindValue(4, $urlCheck->getTitle());
        $stmt->bindValue(5, $urlCheck->getDescription());
        $stmt->bindValue(6, $urlCheck->getCreatedAt());
        $stmt->execute();
    }
}
