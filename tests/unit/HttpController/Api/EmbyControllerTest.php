<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api;

use Movary\Domain\User\UserApi;
use Movary\HttpController\Api\EmbyController;
use Movary\HttpController\Api\RequestMapper\EmbyWebhookRequestMapper;
use Movary\Service\Emby\EmbyScrobbler;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(EmbyController::class)]
class EmbyControllerTest extends TestCase
{
    public function testReturnsNotFoundBeforeMappingPayloadForUnknownWebhook() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['id' => 'secret']);
        $userApi = $this->createStub(UserApi::class);
        $userApi->method('findUserIdByEmbyWebhookId')->willReturn(null);
        $requestMapper = $this->createMock(EmbyWebhookRequestMapper::class);
        $requestMapper->expects(self::never())->method('mapRequest');

        $response = (new EmbyController(
            $userApi,
            $this->createStub(EmbyScrobbler::class),
            $requestMapper,
        ))->handleEmbyWebhook($request);

        self::assertSame(404, $response->getStatusCode()->getCode());
    }

    public function testDelegatesValidatedPayload() : void
    {
        $payload = ['Event' => 'playback.start', 'Item' => ['Type' => 'Movie']];
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['id' => 'secret']);
        $userApi = $this->createStub(UserApi::class);
        $userApi->method('findUserIdByEmbyWebhookId')->willReturn(42);
        $requestMapper = $this->createMock(EmbyWebhookRequestMapper::class);
        $requestMapper->expects(self::once())->method('mapRequest')->with($request)->willReturn($payload);
        $scrobbler = $this->createMock(EmbyScrobbler::class);
        $scrobbler->expects(self::once())->method('processEmbyWebhook')->with(42, $payload);

        $response = (new EmbyController($userApi, $scrobbler, $requestMapper))->handleEmbyWebhook($request);

        self::assertSame(200, $response->getStatusCode()->getCode());
    }
}
