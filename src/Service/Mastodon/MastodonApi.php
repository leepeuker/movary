<?php declare(strict_types=1);

namespace Movary\Service\Mastodon;

use GuzzleHttp\Client as HttpClient;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class MastodonApi
{
    private const int DEFAULT_TIMEOUT = 4;

    public function __construct(
        private readonly HttpClient $httpClient,
    ) {
    }

    public function createPost(string $apiToken, string $username, string $visibility, string $message) : void
    {
        if (preg_match('/@.*@(.*)/', $username, $matches) === false) {
            throw new RuntimeException('Could not extract domain from username: ' . $username);
        }

        $domain = $matches[1] ?? null;
        if ($domain === null) {
            throw new RuntimeException('Could not extract domain from username: ' . $username);
        }

        $requestUrl = 'https://' . $username . '/api/v1/statuses';
        $requestOptions = [
            'connect_timeout' => self::DEFAULT_TIMEOUT,
            'headers' => [
                'Authorization' => 'Bearer ' . $apiToken,
                'Idempotency-Key' => Uuid::uuid4()->toString(),
            ],
            'form_params' => [
                'status' => $message,
                'visibility' => $visibility,
            ],
        ];

        $response = $this->httpClient->request('POST', $requestUrl, $requestOptions);

        $statusCode = $response->getStatusCode();
        if ($statusCode > 400) {
            throw new RuntimeException('Mastodon API request failed with status code: ' . $statusCode);
        }
    }
}
