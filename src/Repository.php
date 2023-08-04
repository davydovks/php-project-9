<?php

namespace Repository;

interface Repository
{
    public function save(array $item): void;
    public function find(string $field, mixed $value): array;
    public function all(): array;
    public function destroy(int $id): void;
}
