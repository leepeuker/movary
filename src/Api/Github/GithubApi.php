<?php declare(strict_types=1);

namespace Movary\Api\Github;

use Exception;
use GuzzleHttp\Client;
use Movary\ValueObject\Url;
use Psr\Log\LoggerInterface;

class GithubApi
{
    private const GITHUB_LATEST_RELEASES_URL = 'https://api.github.com/repos/leepeuker/movary/releases?per_page=10';
    private const GITHUB_LATEST_RELEASE_URL = 'https://api.github.com/repos/leepeuker/movary/releases/latest';

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

    public function fetchLatestMovaryReleases() : GithubReleaseDtoList
    {
//        $releases = GithubReleaseDtoList::create();
//
//        $releases->add(ReleaseDto::create('0.47.3', Url::createFromString('https://github.com/leepeuker/movary/releases/tag/0.47.3')));
//        $releases->add(ReleaseDto::create('0.47.2', Url::createFromString('https://github.com/leepeuker/movary/releases/tag/0.47.2')));
//        $releases->add(ReleaseDto::create('0.47.1', Url::createFromString('https://github.com/leepeuker/movary/releases/tag/0.47.1')));
//        $releases->add(ReleaseDto::create('0.47.0', Url::createFromString('https://github.com/leepeuker/movary/releases/tag/0.47.0')));
//
//        return $this->releaseMapper->mapReleasesFromApiJsonResponse($response->getBody()->getContents());

        $response = $this->httpClient->get(self::GITHUB_LATEST_RELEASES_URL);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException();
        }

        return $this->releaseMapper->mapReleasesFromApiJsonResponse($response->getBody()->getContents());
    }
}
