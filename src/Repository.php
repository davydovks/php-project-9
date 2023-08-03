<?php

namespace Repository;

interface Repository
{
    public function save(array $item): void;
    public function find(int $id): array;
    public function all(): array;
    public function destroy(int $id): void;
}
