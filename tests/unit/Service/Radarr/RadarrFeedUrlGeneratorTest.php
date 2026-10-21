<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\Radarr;

use Movary\Service\ApplicationUrlService;
use Movary\Service\Radarr\RadarrFeedUrlGenerator;
use Movary\ValueObject\RelativeUrl;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RadarrFeedUrlGenerator::class)]
class RadarrFeedUrlGeneratorTest extends TestCase
{
    public function testBuildsCanonicalFeedUrl() : void
    {
        $applicationUrl = $this->createMock(ApplicationUrlService::class);
        $applicationUrl
            ->expects(self::once())
            ->method('createApplicationUrl')
            ->with(RelativeUrl::create('/feeds/radarr/secret'))
            ->willReturn('https://movary.example/feeds/radarr/secret');

        $url = (new RadarrFeedUrlGenerator($applicationUrl))->generateUrl('secret');

        self::assertSame('https://movary.example/feeds/radarr/secret', $url);
    }
}
