<?php declare(strict_types=1);

namespace Movary\Api\Jellyfin;

use GuzzleHttp\Client as HttpClient;
use Movary\Api\Jellyfin\Dto\JellyfinAccessToken;
use Movary\Api\Jellyfin\Exception\JellyfinInvalidAuthentication;
use Movary\Api\Jellyfin\Exception\JellyfinNotFoundError;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Util\Json;
use Movary\ValueObject\Url;
use RuntimeException;

class JellyfinClient
{
    private Url $baseUrl;
    
    public function __construct(
        private readonly HttpClient $httpClient,
        private readonly JellyfinAccessToken $jellyfinAccessToken,
        private readonly Authentication $authenticationService,
        private readonly UserApi $userApi
    ) { 
        $this->baseUrl = $this->userApi->findJellyfinServerUrl($this->authenticationService->getCurrentUserId());
    }

    public function get(string $relativeUrl, array $getParameters = [])
    {
        $getParametersRendered = '?';

        foreach ($getParameters as $name => $getParameter) {
            $getParametersRendered .= $name . '=' . $getParameter . '&';
        }

        $url = $this->baseUrl . $relativeUrl . $getParametersRendered;
        $response = $this->httpClient->request('GET', $url);

        $statusCode = $response->getStatusCode();

        match (true) {
            $statusCode === 401 => JellyfinInvalidAuthentication::create(),
            $statusCode === 404 => JellyfinNotFoundError::create(Url::createFromString($url)),
            $statusCode !== 200 => throw new RuntimeException('Api error. Response status code: ' . $statusCode),
            default => true
        };

        return Json::decode((string)$response->getBody());
    }
}