<?php declare(strict_types=1);

namespace Movary\Api\Github;

use GuzzleHttp\Client;
use Movary\Util\Json;
use Movary\ValueObject\Url;

class GithubApi
{
    private const GITHUB_RELEASES_URL = 'https://api.github.com/repos/leepeuker/movary/releases';

    public function __construct(
        private readonly Client $httpClient,
    ) {
    }

    public function findLatestApplicationLatestVersion() : ?ReleaseDto
    {
        try {
            $response = $this->httpClient->get(self::GITHUB_RELEASES_URL);
        } catch (\Exception $e) {
            return null;
        }

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        $releases = Json::decode($response->getBody()->getContents());

        return ReleaseDto::create(
            $releases[0]['name'],
            Url::createFromString($releases[0]['html_url']),
        );
    }
}
