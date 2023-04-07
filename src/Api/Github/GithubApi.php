<?php declare(strict_types=1);

namespace Movary\Api\Github;

use Exception;
use GuzzleHttp\Client;
use Movary\Util\Json;
use Movary\ValueObject\Url;
use Psr\Log\LoggerInterface;

class GithubApi
{
    private const GITHUB_RELEASES_URL = 'https://api.github.com/repos/leepeuker/movary/releases';

    public function __construct(
        private readonly Client $httpClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function fetchMovaryReleases() : ?ReleaseDtoList
    {
        $releases = ReleaseDtoList::create();

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

        $responseReleases = Json::decode($response->getBody()->getContents());

        foreach ($responseReleases as $responseRelease) {
            $releases->add(
                ReleaseDto::create(
                    $responseRelease['name'],
                    Url::createFromString($responseRelease['html_url']),
                ),
            );
        }

        return $releases;
    }
}
