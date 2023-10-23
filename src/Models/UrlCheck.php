<?php

namespace App\Models;

use Carbon\Carbon;

class UrlCheck
{
    private ?int $id;
    private ?int $urlId;
    private int $statusCode;
    private string $h1;
    private string $title;
    private string $description;
    private string $createdAt;

    public function __construct(array $check)
    {
        $this->id = $check['id'] ?? null;
        $this->urlId = $check['url_id'] ?? null;
        $this->statusCode = $check['status_code'];
        $this->h1 = $check['h1'];
        $this->title = $check['title'];
        $this->description = $check['description'];
        $this->createdAt = $check['created_at'] ?? Carbon::now()->toDateTimeString();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUrlId()
    {
        return $this->urlId;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getH1()
    {
        return $this->h1;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setUrlId(int $urlId)
    {
        $this->urlId = $urlId;
    }
}
