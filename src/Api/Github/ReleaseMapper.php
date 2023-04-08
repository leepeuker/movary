<?php declare(strict_types=1);

namespace Movary\Api\Github;

use Movary\Util\Json;
use Movary\ValueObject\Url;

class ReleaseMapper
{
    public function mapFromApiJsonResponse(string $releasesData) : ReleaseDtoList
    {
        $releases = ReleaseDtoList::create();

        foreach (Json::decode($releasesData) as $releaseData) {
            $releases->add(
                ReleaseDto::create(
                    $releaseData['name'],
                    Url::createFromString($releaseData['html_url']),
                ),
            );
        }

        return $releases;
    }
}
