<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api;

use Movary\Domain\User\UserApi;
use Movary\HttpController\Api\PlexController;
use Movary\HttpController\Api\RequestMapper\PlexWebhookRequestMapper;
use Movary\Service\Plex\PlexScrobbler;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PlexController::class)]
class PlexControllerTest extends TestCase
{
    public function testReturnsNotFoundBeforeMappingPayloadForUnknownWebhook() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['id' => 'secret']);
        $userApi = $this->createStub(UserApi::class);
        $userApi->method('findUserIdByPlexWebhookId')->willReturn(null);
        $requestMapper = $this->createMock(PlexWebhookRequestMapper::class);
        $requestMapper->expects(self::never())->method('mapRequest');

        $response = (new PlexController(
            $userApi,
            $this->createStub(PlexScrobbler::class),
            $requestMapper,
        ))->handlePlexWebhook($request);

        self::assertSame(404, $response->getStatusCode()->getCode());
    }

    public function testDelegatesValidatedPayload() : void
    {
        $payload = ['event' => 'library.new'];
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['id' => 'secret']);
        $userApi = $this->createStub(UserApi::class);
        $userApi->method('findUserIdByPlexWebhookId')->willReturn(42);
        $requestMapper = $this->createMock(PlexWebhookRequestMapper::class);
        $requestMapper->expects(self::once())->method('mapRequest')->with($request)->willReturn($payload);
        $scrobbler = $this->createMock(PlexScrobbler::class);
        $scrobbler->expects(self::once())->method('processPlexWebhook')->with(42, $payload);

        $response = (new PlexController($userApi, $scrobbler, $requestMapper))->handlePlexWebhook($request);

        self::assertSame(200, $response->getStatusCode()->getCode());
    }
}
