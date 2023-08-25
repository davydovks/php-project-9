<?php

namespace PageAnalyzer;

use Carbon\Carbon;
use GuzzleHttp\Client;
use DiDom\Document;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;

class Parser
{
    public static function getUrlData(array $url): array
    {
        $client = new Client();
        try {
            $urlResponse = $client->get($url['name']);
        } catch (ClientException $e) {
            $urlResponse = $e->getResponse();
        } catch (ConnectException | ServerException) {
            return [];
        } catch (RequestException) {
            return ['status_code' => 500];
        }

        $document = new Document($urlResponse->getBody()->__toString());
        $h1 = optional($document->first('h1'))->innerHtml() ?? '';
        $title = optional($document->first('title'))->innerHtml() ?? '';
        $description = optional($document->first('meta[name=description]'))->content ?? '';

        $check = [
            'url_id' => $url['id'],
            'status_code' => $urlResponse->getStatusCode(),
            'h1' => mb_substr($h1, 0, 255),
            'title' => mb_substr($title, 0, 255),
            'description' => mb_substr($description, 0, 255),
            'created_at' => Carbon::now()->toDateTimeString()
        ];

        return $check;
    }

    public static function normalizeUrl(mixed $rawUrl): array
    {
        if (isset($rawUrl['name'])) {
            $parsedUrl = parse_url($rawUrl['name']);
            $scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '';
            $host = isset($parsedUrl['host']) ? $parsedUrl['host'] : '';
            $normalizedUrl = mb_strtolower("{$scheme}{$host}");
        } else {
            $normalizedUrl = '';
        }

        return ['name' => $normalizedUrl];
    }
}
