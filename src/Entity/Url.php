<?php

namespace App\Entity;

use Carbon\Carbon;

class Url
{
    private ?int $id;
    private string $name;
    private string $createdAt;
    private ?int $lastCheckStatus;
    private ?string $lastCheckedAt;

    public function __construct(array $url)
    {
        $this->id = $url['id'] ?? null;
        $this->name = $url['name'];
        $this->createdAt = $url['created_at'] ?? Carbon::now()->toDateTimeString();
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

    public function getLastCheckStatus()
    {
        return $this->lastCheckStatus ?? null;
    }

    public function getLastCheckedAt()
    {
        return $this->lastCheckedAt ?? null;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function setCreatedAt(?string $timestamp = null)
    {
        $this->createdAt = $timestamp ? $timestamp : Carbon::now()->toDateTimeString();
    }

    public function setLastCheckStatus(int $status)
    {
        $this->lastCheckStatus = $status;
    }

    public function setLastCheckedAt(string $timestamp)
    {
        $this->lastCheckedAt = $timestamp;
    }
}
