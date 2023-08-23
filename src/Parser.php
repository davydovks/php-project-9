<?php

namespace PageAnalyzer;

use Carbon\Carbon;
use GuzzleHttp\Client;
use DiDom\Document;

class Parser
{
    public static function getUrlData(array $url): array
    {
        $client = new Client();
        try {
            $urlResponse = $client->get($url['name']);

            $document = new Document($url['name'], true);
            $h1 = $document->find('h1')[0]->innerHtml() ?? '';
            $title = $document->find('title')[0]->innerHtml() ?? '';
            $description = $document->find('meta[name=description]')[0]->content ?? '';

            $check = [
                'url_id' => $url['id'],
                'status_code' => $urlResponse->getStatusCode(),
                'h1' => mb_substr($h1, 0, 255),
                'title' => mb_substr($title, 0, 255),
                'description' => mb_substr($description, 0, 255),
                'created_at' => Carbon::now()->toDateTimeString()
            ];
        } catch (\Exception $e) {
            $check = [];
        }
        return $check;
    }
}
