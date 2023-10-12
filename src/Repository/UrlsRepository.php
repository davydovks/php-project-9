<?php

namespace App\Repository;

use App\Entity\Url;
use App\Connection;

class UrlsRepository
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

    public function all()
    {
        $sql = "SELECT * FROM urls ORDER BY id DESC";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $checks = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            return array_map(function ($url) {
                return new Url($url);
            }, $checks);
        } catch (\PDOException $e) {
            echo $e->getMessage();
            return [];
        }
    }

    public function findOneById(int $id): ?Url
    {
        $sql = "SELECT * FROM urls WHERE id = '{$id}'";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $url = $stmt->fetch(\PDO::FETCH_ASSOC);
            return is_array($url) ? new Url($url) : null;
        } catch (\PDOException $e) {
            echo $e->getMessage();
            return null;
        }
    }

    public function findOneByName(string $name): ?Url
    {
        $sql = "SELECT * FROM urls WHERE name = ?";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(1, $name);
            $stmt->execute();
            $url = $stmt->fetch(\PDO::FETCH_ASSOC);
            return is_array($url) ? new Url($url) : null;
        } catch (\PDOException $e) {
            echo $e->getMessage();
            return null;
        }
    }

    public function save(Url $url): string
    {
        $sql = "INSERT INTO urls (name, created_at) VALUES (?, ?) RETURNING id";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(1, $url->getName());
            $stmt->bindValue(2, $url->getCreatedAt());
            $stmt->execute();
            [$id] = $stmt->fetch(\PDO::FETCH_NUM);
            return $id;
        } catch (\PDOException $e) {
            echo $e->getMessage();
            return '';
        }
    }
}
