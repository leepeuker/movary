<?php declare(strict_types=1);

namespace Movary\Api\Tmdb;

use GuzzleHttp\Psr7\Request;
use Movary\Api\Tmdb\Exception\TmdbAuthorizationError;
use Movary\Api\Tmdb\Exception\TmdbResourceNotFound;
use Movary\Service\ServerSettings;
use Movary\Util\Json;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class TmdbClient
{
    private const string BASE_URL = 'https://api.themoviedb.org/3';

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly ServerSettings $serverSettingsService,
    ) {
    }

    public function get(string $relativeUrl, array $getParameters = []) : array
    {
        $url = $this->buildUrl($relativeUrl, $getParameters);

        $request = new Request('GET', $url);

        $response = $this->httpClient->sendRequest($request);

        $statusCode = $response->getStatusCode();

        match (true) {
            $statusCode === 401 => throw TmdbAuthorizationError::create(),
            $statusCode === 404 => $this->handleNotFound($url, $response),
            $statusCode !== 200 => throw new RuntimeException('Api error. Response status code: ' . $statusCode),
            default => true
        };

        return Json::decode((string)$response->getBody());
    }

    private function buildUrl(string $relativeUrl, array $getParameters) : string
    {
        $getParametersRendered = '?';

        foreach ($getParameters as $name => $getParameter) {
            $getParametersRendered .= $name . '=' . $getParameter . '&';
        }

        $getParametersRendered .= 'api_key=' . $this->serverSettingsService->getTmdbApiKey();

        return self::BASE_URL . $relativeUrl . $getParametersRendered;
    }

    private function handleNotFound(string $url, ResponseInterface $response) : never
    {
        $responseContent = Json::decode((string)$response->getBody());

        if (isset($responseContent['success'], $responseContent['status_code']) === true &&
            $responseContent['success'] === false &&
            $responseContent['status_code'] === 34) {
            throw TmdbResourceNotFound::create($url);
        }

        throw new RuntimeException('Api error. Response status code: 404');
    }
}
