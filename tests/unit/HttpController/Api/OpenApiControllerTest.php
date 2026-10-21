<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api;

use Movary\HttpController\Api\OpenApiController;
use Movary\Service\ApplicationUrlService;
use Movary\Service\ServerSettings;
use Movary\Util\File;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OpenApiController::class)]
class OpenApiControllerTest extends TestCase
{
    public function testInjectsRuntimeVersionAndApiUrl() : void
    {
        $file = $this->createMock(File::class);
        $file->expects(self::once())->method('readFile')->with('/docs/openapi.json')->willReturn(
            '{"openapi":"3.0.2","info":{"version":""},"servers":[]}',
        );
        $settings = $this->createStub(ServerSettings::class);
        $settings->method('getApplicationVersion')->willReturn('1.2.3');
        $url = $this->createStub(ApplicationUrlService::class);
        $url->method('createApplicationUrl')->willReturn('https://movary.example');

        $response = (new OpenApiController($file, $settings, $url, '/docs/'))->getSchema();

        self::assertSame(
            '{"openapi":"3.0.2","info":{"version":"1.2.3"},"servers":[{"url":"https:\/\/movary.example"}]}',
            $response->getBody(),
        );
    }
}
