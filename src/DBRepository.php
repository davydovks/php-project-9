<?php

namespace Repository;

use Database\Connection;

class DBRepository implements Repository 
{
    private $itemName;
    private \PDO $pdo;

    public function __construct(string $itemName)
    {
        $this->itemName = $itemName;

        try {
            $this->pdo = Connection::get()->connect();
            /*$sql = "CREATE TABLE IF NOT EXISTS {$this->itemName}";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();*/
        } catch (\PDOException $e) {
            echo $e->getMessage();
        }
    }

    public function save(array $item, string $created_at = null): void
    {
        $sql = "INSERT INTO {$this->itemName} (name, created_at) VALUES (:name, :created_at)";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':name', $item['name']);
            $stmt->bindParam(':created_at', $created_at);
            $stmt->execute();
        } catch (\PDOException $e) {
            echo $e->getMessage();
        }
    }

    public function find(string $field, mixed $value): array
    {
        $sql = "SELECT * FROM {$this->itemName} WHERE {$field} = '{$value}'";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $result = [];
            foreach ($stmt as $row) {
                $result[] = $row;
            }
            return $result;
        } catch (\PDOException $e) {
            echo $e->getMessage();
        }
    }

    public function all(): array
    {
        $sql = "SELECT * FROM {$this->itemName}";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $result = [];
            foreach ($stmt as $row) {
                $result[] = $row;
            }
            return $result;
        } catch (\PDOException $e) {
            echo $e->getMessage();
        }
    }

    public function destroy(int $id): void
    {
        $sql = "DELETE FROM {$this->itemName} WHERE id = {$id}";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
        } catch (\PDOException $e) {
            echo $e->getMessage();
        }
    }

    public function clear(): void
    {
        $sql = "DELETE FROM {$this->itemName}";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
        } catch (\PDOException $e) {
            echo $e->getMessage();
        }
    }
}