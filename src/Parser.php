<?php

namespace App;

use DiDom\Document;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;

class Parser
{
    public static function parseResponse(ResponseInterface $urlResponse): array
    {
        $document = new Document($urlResponse->getBody()->__toString());
        $h1 = optional($document->first('h1'))->text();
        $title = optional($document->first('title'))->text();
        $description = optional($document->first('meta[name=description]'))->content;
        return [
            'status_code' => $urlResponse->getStatusCode(),
            'h1' => mb_substr($h1, 0, 255),
            'title' => $title,
            'description' => strip_tags($description)
        ];
    }

    public static function normalizeUrl(array $rawUrl): string
    {
        $parsedUrl = parse_url($rawUrl['name']);
        $scheme = Arr::get($parsedUrl, 'scheme', '');
        $host = Arr::get($parsedUrl, 'host', '');

        if (strlen($scheme) == 0) {
            return mb_strtolower($host);
        }

        return mb_strtolower("{$scheme}://{$host}");
    }
}
