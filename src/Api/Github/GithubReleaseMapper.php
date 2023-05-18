<?php declare(strict_types=1);

namespace Movary\Api\Github;

use Movary\Util\Json;
use Movary\ValueObject\Url;

class GithubReleaseMapper
{
    public function mapReleaseFromApiJsonResponse(string $releasesData) : GithubReleaseDto
    {
        $releasesDataDecoded = Json::decode($releasesData);

        return GithubReleaseDto::create(
            $releasesDataDecoded['name'],
            Url::createFromString($releasesDataDecoded['html_url']),
        );
    }

    public function mapReleasesFromApiJsonResponse(string $releasesData) : GithubReleaseDtoList
    {
        $releases = GithubReleaseDtoList::create();

        foreach (Json::decode($releasesData) as $releaseData) {
            $releases->add(
                GithubReleaseDto::create(
                    $releaseData['name'],
                    Url::createFromString($releaseData['url']),
                ),
            );
        }

        return $releases;
    }
}
