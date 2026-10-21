<?php declare(strict_types=1);

namespace Tests\Http\Movary;

use GuzzleHttp\Client;

class HttpClient
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
    public function get(string $path, array $headers = []) : HttpResponse
    {
        return $this->request('GET', $path, $headers);
    }

    /**
     * @param array<array-key, mixed> $body
     * @param array<string, string> $headers
     */
    public function post(string $path, array $body, array $headers = []) : HttpResponse
    {
        return $this->request('POST', $path, $headers, $body);
    }

    /** @param array<string, string> $fields */
    public function postForm(string $path, array $fields) : HttpResponse
    {
        return new HttpResponse($this->client->post(ltrim($path, '/'), ['form_params' => $fields]));
    }

    /** @param array<string, string> $fields */
    public function postMultipart(string $path, array $fields) : HttpResponse
    {
        $multipart = [];
        foreach ($fields as $name => $contents) {
            $multipart[] = ['name' => $name, 'contents' => $contents];
        }

        return new HttpResponse($this->client->post(ltrim($path, '/'), ['multipart' => $multipart]));
    }

    /**
     * @param array<array-key, mixed> $body
     * @param array<string, string> $headers
     */
    public function put(string $path, array $body, array $headers = []) : HttpResponse
    {
        return $this->request('PUT', $path, $headers, $body);
    }

    /**
     * @param array<array-key, mixed> $body
     * @param array<string, string> $headers
     */
    public function delete(string $path, array $body, array $headers = []) : HttpResponse
    {
        return $this->request('DELETE', $path, $headers, $body);
    }

    /**
     * @param array<string, string> $headers
     * @param null|array<array-key, mixed> $body
     */
    public function request(string $method, string $path, array $headers = [], ?array $body = null) : HttpResponse
    {
        $options = ['headers' => $headers];
        if ($body !== null) {
            $options['json'] = $body;
        }

        return new HttpResponse($this->client->request($method, ltrim($path, '/'), $options));
    }

    /** @param array<string, string> $headers */
    public function requestRaw(string $method, string $path, string $body, array $headers = []) : HttpResponse
    {
        return new HttpResponse($this->client->request($method, ltrim($path, '/'), [
            'body' => $body,
            'headers' => $headers,
        ]));
    }
}
