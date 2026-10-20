<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web;

use Movary\HttpController\Web\ManifestController;
use Movary\Service\ServerSettings;
use Movary\Util\Json;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ManifestController::class)]
class ManifestControllerTest extends TestCase
{
    public function testRenderUsesConfiguredApplicationName() : void
    {
        $serverSettings = $this->createStub(ServerSettings::class);
        $serverSettings->method('getApplicationName')->willReturn('My Movies');

        $response = (new ManifestController($serverSettings))->render();
        $manifest = Json::decode((string)$response->getBody());

        self::assertSame('My Movies', $manifest['name']);
        self::assertSame('My Movies', $manifest['short_name']);
        self::assertContains('Content-Type: application/manifest+json', array_map('strval', $response->getHeaders()));
    }

    public function testRenderFallsBackToMovaryApplicationName() : void
    {
        $serverSettings = $this->createStub(ServerSettings::class);
        $serverSettings->method('getApplicationName')->willReturn(null);

        $response = (new ManifestController($serverSettings))->render();
        $manifest = Json::decode((string)$response->getBody());

        self::assertSame('Movary', $manifest['name']);
        self::assertSame('Movary', $manifest['short_name']);
    }
}
