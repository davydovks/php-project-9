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
            $this->createTableIfNotExists($itemName);
        } catch (\PDOException $e) {
            echo $e->getMessage();
        }
    }

    public function createTableIfNotExists(string $tableName): void
    {
        $sqlFile = __DIR__ . "/../database/{$tableName}.sql";
        $sql = file_get_contents($sqlFile);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
    }

    public function save(array $item): void
    {
        $columns = [];
        $values = [];
        foreach ($item as $key => $value) {
            $columns[] = $key;
            $values[] = ':' . $key;
        }
        $colStr = implode(', ', $columns);
        $valStr = implode(', ', $values);
        $sql = "INSERT INTO {$this->itemName} ($colStr) VALUES ($valStr)";
        try {
            $stmt = $this->pdo->prepare($sql);
            foreach (array_keys($item) as $key) {
                $stmt->bindParam(':' . $key, $item[$key]);
            }
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
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return is_array($row) ? $row : [];
        } catch (\PDOException $e) {
            echo $e->getMessage();
        }
    }

    public function findLast(string $field, mixed $value): array
    {
        $sql = "SELECT * FROM {$this->itemName} WHERE {$field} = '{$value}' ORDER BY id DESC LIMIT 1";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return is_array($row) ? $row : [];
        } catch (\PDOException $e) {
            echo $e->getMessage();
        }
    }

    public function all(): array
    {
        $sql = "SELECT * FROM {$this->itemName} ORDER BY id DESC";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
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
