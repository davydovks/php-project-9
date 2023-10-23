<?php

namespace App\Repository;

use App\Models\UrlCheck;
use App\Connection;

class UrlChecksRepository
{
    private \PDO $pdo;

    public function __construct(Connection $pdo)
    {
        try {
            $this->pdo = $pdo->connect();
        } catch (\PDOException $e) {
            echo $e->getMessage();
        }
    }

    public function findAllByUrlId(int $urlId)
    {
        $sql = "SELECT * FROM url_checks WHERE url_id = ? ORDER BY id DESC";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(1, $urlId, \PDO::PARAM_INT);
            $stmt->execute();
            $checks = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            return array_map(function ($check) {
                return new UrlCheck($check);
            }, $checks);
        } catch (\PDOException $e) {
            echo $e->getMessage();
            return [];
        }
    }

    public function findLastByUrlId(int $urlId)
    {
        $sql = "SELECT * FROM url_checks WHERE url_id = ? ORDER BY id DESC LIMIT 1";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(1, $urlId, \PDO::PARAM_INT);
            $stmt->execute();
            $check = $stmt->fetch(\PDO::FETCH_ASSOC);
            return is_array($check) ? new UrlCheck($check) : null;
        } catch (\PDOException $e) {
            echo $e->getMessage();
            return null;
        }
    }

    public function save(UrlCheck $urlCheck): void
    {
        $columns = "url_id, status_code, h1, title, description, created_at";
        $values = "?, ?, ?, ?, ?, ?";
        $sql = "INSERT INTO url_checks ({$columns}) VALUES ({$values}) RETURNING id";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(1, $urlCheck->getUrlId());
            $stmt->bindValue(2, $urlCheck->getStatusCode());
            $stmt->bindValue(3, $urlCheck->getH1());
            $stmt->bindValue(4, $urlCheck->getTitle());
            $stmt->bindValue(5, $urlCheck->getDescription());
            $stmt->bindValue(6, $urlCheck->getCreatedAt());
            $stmt->execute();
        } catch (\PDOException $e) {
            echo $e->getMessage();
        }
    }
}
