<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service;

use Movary\Service\ApplicationUrlService;
use Movary\Service\WebhookUrlBuilder;
use Movary\ValueObject\RelativeUrl;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(WebhookUrlBuilder::class)]
class WebhookUrlBuilderTest extends TestCase
{
    #[DataProvider('providerWebhook')]
    public function testBuildsCanonicalWebhookUrl(string $provider) : void
    {
        $applicationUrl = $this->createMock(ApplicationUrlService::class);
        $applicationUrl->method('hasApplicationUrl')->willReturn(true);
        $applicationUrl
            ->expects(self::once())
            ->method('createApplicationUrl')
            ->with(RelativeUrl::create('/webhooks/' . $provider . '/secret'))
            ->willReturn('https://movary.example/webhooks/' . $provider . '/secret');

        $urlBuilder = new WebhookUrlBuilder($applicationUrl);
        $url = match ($provider) {
            'plex' => $urlBuilder->buildPlexWebhookUrl('secret'),
            'jellyfin' => $urlBuilder->buildJellyfinWebhookUrl('secret'),
            'emby' => $urlBuilder->buildEmbyWebhookUrl('secret'),
            'kodi' => $urlBuilder->buildKodiWebhookUrl('secret'),
        };

        self::assertSame('https://movary.example/webhooks/' . $provider . '/secret', $url);
    }

    /** @return array<string, array{string}> */
    public static function providerWebhook() : array
    {
        return [
            'Plex' => ['plex'],
            'Jellyfin' => ['jellyfin'],
            'Emby' => ['emby'],
            'Kodi' => ['kodi'],
        ];
    }
}
