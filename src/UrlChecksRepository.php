<?php

namespace App\Repository;

use App\Entity\UrlCheck;
use Database\Connection;

class UrlChecksRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        try {
            $this->pdo = Connection::get()->connect();
        } catch (\PDOException $e) {
            echo $e->getMessage();
        }
    }

    public function findAllByUrlId(int $urlId)
    {
        $sql = "SELECT * FROM url_checks WHERE url_id = ? ORDER BY id DESC";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(1, $urlId, \PDO::PARAM_INT);
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
            $stmt->bindParam(1, $urlId, \PDO::PARAM_INT);
            $stmt->execute();
            $check = $stmt->fetch(\PDO::FETCH_ASSOC);
            return is_array($check) ? new UrlCheck($check) : null;
        } catch (\PDOException $e) {
            echo $e->getMessage();
            return null;
        }
    }

    public function save(UrlCheck $urlCheck): int
    {
        $columns = "url_id, status_code, h1, title, description, created_at";
        $values = "?, ?, ?, ?, ?, ?";
        $sql = "INSERT INTO url_checks ({$columns}) VALUES ({$values}) RETURNING id";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(1, $urlCheck->getUrlId());
            $stmt->bindParam(2, $urlCheck->getStatusCode());
            $stmt->bindParam(3, $urlCheck->getH1());
            $stmt->bindParam(4, $urlCheck->getTitle());
            $stmt->bindParam(5, $urlCheck->getDescription());
            $stmt->bindParam(6, $urlCheck->getCreatedAt());
            $stmt->execute();
            [$id] = $stmt->fetch(\PDO::FETCH_NUM);
            return $id;
        } catch (\PDOException $e) {
            echo $e->getMessage();
        }
    }
}
