<?php declare(strict_types=1);

namespace Tests\Api\Movary\Support;

use GuzzleHttp\Client;

class ApiClient
{
    private readonly Client $client;

    public function __construct(string $baseUrl)
    {
        $this->client = new Client([
            'base_uri' => rtrim($baseUrl, '/') . '/',
            'connect_timeout' => 2,
            'http_errors' => false,
            'timeout' => 10,
        ]);
    }

    /** @param array<string, string> $headers */
    public function get(string $path, array $headers = []) : ApiResponse
    {
        return new ApiResponse($this->client->get(ltrim($path, '/'), ['headers' => $headers]));
    }
}
