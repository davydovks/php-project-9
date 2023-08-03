<?php

namespace Repository;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\Interfaces\ResponseInterface;

class CookieRepository implements Repository
{
    private $itemName;

    private function getNextId($request)
    {
        $items = $this->all($request);
        $ids = array_map(fn($item) => $item['id'], $items);
        $maxId = empty($ids) ? 0 : max($ids);
        $nextId = is_int($maxId) ? $maxId + 1 : 1;
        return $nextId;
    }

    public function __construct(string $itemName)
    {
        $this->itemName = $itemName;
    }

    public function save(
        array $item,
        string $created_at = null,
        ServerRequestInterface $request = null,
        ResponseInterface &$response = null
    ): void {
        if (!isset($item['id'])) {
            $item['id'] = $this->getNextId($request);
        } else {
            $this->destroy($item['id'], $request, $response);
        }

        if ($created_at !== null) {
            $item['created_at'] = $created_at;
        }

        $cookie = $this->all($request);
        $cookie[] = $item;
        $encodedCookie = json_encode($cookie);

        $response = $response->withHeader('Set-Cookie', "{$this->itemName}={$encodedCookie}");
    }

    public function all($request = null): array
    {
        return json_decode($request->getCookieParam($this->itemName, json_encode([])), true) ?? [];
    }

    public function find(int $id, ServerRequestInterface $request = null): array
    {
        $cookie = $this->all($request);
        //return array_filter($cookie, fn($item) => $item['id'] == $id);
        return array_reduce($cookie, function ($carry, $item) use ($id) {
            return $item['id'] == $id ? $item : $carry;
        }, []);
    }

    public function destroy(int $id, ServerRequestInterface $request = null, ResponseInterface &$response = null): void
    {
        $cookie = $this->all($request);
        $filteredCookie = array_filter($cookie, fn($item) => $item['id'] != $id);
        $encodedCookie = json_encode($filteredCookie);

        $response = $response->withHeader('Set-Cookie', "{$this->itemName}={$encodedCookie}");
    }

    public function clear(ResponseInterface &$response)
    {
        $response = $response->withHeader('Set-Cookie', "{$this->itemName}=");
    }
}
