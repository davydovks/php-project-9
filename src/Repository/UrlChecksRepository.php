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
        $sql = "SELECT * FROM url_checks WHERE url_id = :url_id ORDER BY id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue('url_id', $urlId, \PDO::PARAM_INT);
        $stmt->execute();
        $checks = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(function ($check) {
            return new UrlCheck($check);
        }, $checks);
    }

    public function findLastByUrlId(int $urlId)
    {
        $sql = "SELECT * FROM url_checks WHERE url_id = :url_id ORDER BY id DESC LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue('url_id', $urlId, \PDO::PARAM_INT);
        $stmt->execute();
        $check = $stmt->fetch(\PDO::FETCH_ASSOC);
        return is_array($check) ? new UrlCheck($check) : null;
    }

    public function save(UrlCheck $urlCheck): void
    {
        $columns = "url_id, status_code, h1, title, description, created_at";
        $values = ":url_id, :status_code, :h1, :title, :description, :created_at";
        $sql = "INSERT INTO url_checks ({$columns}) VALUES ({$values})";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue('url_id', $urlCheck->getUrlId());
        $stmt->bindValue('status_code', $urlCheck->getStatusCode());
        $stmt->bindValue('h1', $urlCheck->getH1());
        $stmt->bindValue('title', $urlCheck->getTitle());
        $stmt->bindValue('description', $urlCheck->getDescription());
        $stmt->bindValue('created_at', $urlCheck->getCreatedAt());
        $stmt->execute();
    }
}
