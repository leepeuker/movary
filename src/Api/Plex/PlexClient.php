<?php declare(strict_types=1);

namespace Movary\Api\Plex;

use GuzzleHttp\Psr7\Request;
use Movary\Util\Json;
use Movary\ValueObject\Config;
use Psr\Http\Client\ClientInterface;
use RuntimeException;

class PlexClient
{
    private const BASE_URL = "https://plex.tv/api/v2";

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly Config $config
    ){
    }

    public function sendGetRequest(string $relativeUrl, array $getParameters = [] ) : Array
    {
        return [];
    }

    public function sendPostRequest(string $relativeUrl, array $headers = []) : Array
    {
        if ($this->config->getAsString('PLEX_IDENTIFIER', '') === '') {
            return [];
        }
        $url = self::BASE_URL . $relativeUrl;

        $headers['X-Plex-Client-Identifier'] = $this->config->getAsString('PLEX_IDENTIFIER');

        $request = new Request('post', $url, $headers);
        $response = $this->httpClient->sendRequest($request);
        $statusCode = $response->getStatusCode();
        match(true) {
            // If the status code is smaller than 200 or greater / equal to 300, it'll throw an error
            $statusCode !== 200 && $statusCode !== 201 => throw new RuntimeException('Plex API error. Response status code: '. $statusCode),
            default => true
        };

        return Json::decode((string)$response->getBody());
    }
}