<?php declare(strict_types=1);

namespace Movary\Service\Radarr;

use Movary\Service\ApplicationUrlService;
use Movary\ValueObject\RelativeUrl;

class RadarrFeedUrlGenerator
{
    public function __construct(
        private readonly ApplicationUrlService $applicationUrlService,
    ) {
    }

    public function generateUrl(string $feedId) : string
    {
        return $this->applicationUrlService->createApplicationUrl(
            RelativeUrl::create('/api/feed/radarr/' . $feedId),
        );
    }
}
