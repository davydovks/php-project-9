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
            $h1 = optional($document->first('h1'))->innerHtml() ?? '';
            $title = optional($document->first('title'))->innerHtml() ?? '';
            $description = optional($document->first('meta[name=description]'))->content ?? '';

            $check = [
                'url_id' => $url['id'],
                'status_code' => optional($urlResponse)->getStatusCode(),
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
