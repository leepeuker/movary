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
        return $this->request('GET', $path, $headers);
    }

    /**
     * @param array<array-key, mixed> $body
     * @param array<string, string> $headers
     */
    public function post(string $path, array $body, array $headers = []) : ApiResponse
    {
        return $this->request('POST', $path, $headers, $body);
    }

    /**
     * @param array<array-key, mixed> $body
     * @param array<string, string> $headers
     */
    public function put(string $path, array $body, array $headers = []) : ApiResponse
    {
        return $this->request('PUT', $path, $headers, $body);
    }

    /**
     * @param array<array-key, mixed> $body
     * @param array<string, string> $headers
     */
    public function delete(string $path, array $body, array $headers = []) : ApiResponse
    {
        return $this->request('DELETE', $path, $headers, $body);
    }

    /**
     * @param array<string, string> $headers
     * @param null|array<array-key, mixed> $body
     */
    public function request(string $method, string $path, array $headers = [], ?array $body = null) : ApiResponse
    {
        $options = ['headers' => $headers];
        if ($body !== null) {
            $options['json'] = $body;
        }

        return new ApiResponse($this->client->request($method, ltrim($path, '/'), $options));
    }
}
