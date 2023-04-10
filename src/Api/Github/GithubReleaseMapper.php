<?php declare(strict_types=1);

namespace Movary\Api\Github;

use Movary\Util\Json;
use Movary\ValueObject\Url;

class GithubReleaseMapper
{
    public function mapFromApiJsonResponse(string $releasesData) : GithubReleaseDto
    {
        $releasesDataDecoded = Json::decode($releasesData);

        return GithubReleaseDto::create(
            $releasesDataDecoded['name'],
            Url::createFromString($releasesDataDecoded['html_url']),
        );
    }
}
