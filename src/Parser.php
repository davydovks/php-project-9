<?php

namespace App;

use DiDom\Document;
use Psr\Http\Message\ResponseInterface;

class Parser
{
    public static function parseResponse(ResponseInterface $urlResponse): array
    {
        $document = new Document($urlResponse->getBody()->__toString());
        $h1 = optional($document->first('h1'))->innerHtml() ?? '';
        $title = optional($document->first('title'))->innerHtml() ?? '';
        $description = optional($document->first('meta[name=description]'))->content ?? '';

        $checkData = [
            'status_code' => $urlResponse->getStatusCode(),
            'h1' => strip_tags($h1),
            'title' => strip_tags($title),
            'description' => strip_tags($description)
        ];

        return $checkData;
    }

    public static function normalizeUrl(array $rawUrl): string
    {
        $parsedUrl = parse_url($rawUrl['name']);
        $scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '';
        $host = isset($parsedUrl['host']) ? $parsedUrl['host'] : '';
        $normalizedUrl = mb_strtolower("{$scheme}{$host}");

        return $normalizedUrl;
    }
}
