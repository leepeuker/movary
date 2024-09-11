<?php declare(strict_types=1);

namespace Movary\Api\Github;

use Exception;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class GithubApi
{
    private const string GITHUB_LATEST_RELEASE_URL = 'https://api.github.com/repos/leepeuker/movary/releases/latest';

    public function __construct(
        private readonly Client $httpClient,
        private readonly GithubReleaseMapper $releaseMapper,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function fetchLatestMovaryRelease() : ?GithubReleaseDto
    {
        try {
            $response = $this->httpClient->get(self::GITHUB_LATEST_RELEASE_URL);
        } catch (Exception $e) {
            $this->logger->warning('Could not send request to fetch latest github releases.', ['exception' => $e]);

            return null;
        }

        if ($response->getStatusCode() !== 200) {
            $this->logger->warning('Request to fetch latest github releases failed with status code: ' . $response->getStatusCode());

            return null;
        }

        return $this->releaseMapper->mapReleaseFromApiJsonResponse($response->getBody()->getContents());
    }
}
