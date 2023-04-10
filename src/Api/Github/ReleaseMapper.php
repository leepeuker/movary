<?php declare(strict_types=1);

namespace Movary\Api\Github;

use Movary\Util\Json;
use Movary\ValueObject\Url;

class ReleaseMapper
{
    public function mapFromApiJsonResponse(string $releasesData) : ReleaseDto
    {
        $releasesDataDecoded = Json::decode($releasesData);

        return ReleaseDto::create(
            $releasesDataDecoded['name'],
            Url::createFromString($releasesDataDecoded['html_url']),
        );
    }
}
