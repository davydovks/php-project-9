<?php

namespace PageAnalyzer;

use Carbon\Carbon;
use GuzzleHttp\Client;

class Parser
{
    public static function getUrlData(array $url): array
    {
        $client = new Client();
        try {
            $urlResponse = $client->request('GET', $url['name']);
            $body = $urlResponse->getBody();
            preg_match('/(?<=>)(.*?)(?=<\/h1>)/', $body, $h1Matches);
            preg_match('/(?<=title>)(.*?)(?=<\/title>)/', $body, $titleMatches);
            preg_match('/(?<=\<meta name=\"description\" content=\")(.*?)(?=\">)/', $body, $descMatches);
            $check = [
                'url_id' => $url['id'],
                'status_code' => $urlResponse->getStatusCode(),
                'h1' => substr($h1Matches[0], 0, 255),
                'title' => substr($titleMatches[0], 0, 255),
                'description' => substr($descMatches[0], 0, 255),
                'created_at' => Carbon::now()->toDateTimeString()
            ];
        } catch (\Exception $e) {
            $check = [];
        }

        return $check;
    }
}
