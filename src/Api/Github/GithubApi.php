<?php declare(strict_types=1);

namespace Movary\Api\Github;

use Exception;
use GuzzleHttp\Client;
use Movary\ValueObject\Url;
use Psr\Log\LoggerInterface;

class GithubApi
{
    private const GITHUB_RELEASES_URL = 'https://api.github.com/repos/leepeuker/movary/releases';

    public function __construct(
        private readonly Client $httpClient,
        private readonly ReleaseMapper $releaseMapper,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function fetchMovaryReleases() : ?ReleaseDtoList
    {
        $releases = ReleaseDtoList::create();

        $releases->add(ReleaseDto::create('0.47.3', Url::createFromString('https://github.com/leepeuker/movary/releases/tag/0.47.3')));
        $releases->add(ReleaseDto::create('0.47.2', Url::createFromString('https://github.com/leepeuker/movary/releases/tag/0.47.2')));
        $releases->add(ReleaseDto::create('0.47.1', Url::createFromString('https://github.com/leepeuker/movary/releases/tag/0.47.1')));
        $releases->add(ReleaseDto::create('0.47.0', Url::createFromString('https://github.com/leepeuker/movary/releases/tag/0.47.0')));

        return $releases;

        try {
            $response = $this->httpClient->get(self::GITHUB_RELEASES_URL);
        } catch (Exception $e) {
            $this->logger->warning('Could not send request to fetch github releases.', ['exception' => $e]);

            return $releases;
        }

        if ($response->getStatusCode() !== 200) {
            $this->logger->warning('Request to fetch github releases failed with status code: ' . $response->getStatusCode());

            return $releases;
        }

        $apiJsonResponse = $response->getBody()->getContents();

        return $this->releaseMapper->mapFromApiJsonResponse($apiJsonResponse);
    }
}
