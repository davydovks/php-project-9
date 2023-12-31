<?php

namespace App\Models;

use Carbon\Carbon;

class Url
{
    private ?int $id;
    private string $name;
    private Carbon $createdAt;

    public function __construct(array $url)
    {
        $this->id = $url['id'] ?? null;
        $this->name = $url['name'];
        $this->createdAt = isset($url['created_at'])
            ? new Carbon($url['created_at'])
            : Carbon::now();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }
}
