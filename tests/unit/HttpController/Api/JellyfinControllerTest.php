<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api;

use Movary\Domain\User\UserApi;
use Movary\HttpController\Api\JellyfinController;
use Movary\HttpController\Api\RequestMapper\JellyfinWebhookRequestMapper;
use Movary\Service\Jellyfin\JellyfinScrobbler;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JellyfinController::class)]
class JellyfinControllerTest extends TestCase
{
    public function testReturnsNotFoundBeforeMappingPayloadForUnknownWebhook() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['id' => 'secret']);
        $userApi = $this->createStub(UserApi::class);
        $userApi->method('findUserIdByJellyfinWebhookId')->willReturn(null);
        $requestMapper = $this->createMock(JellyfinWebhookRequestMapper::class);
        $requestMapper->expects(self::never())->method('mapRequest');

        $response = (new JellyfinController(
            $userApi,
            $this->createStub(JellyfinScrobbler::class),
            $requestMapper,
        ))->handleJellyfinWebhook($request);

        self::assertSame(404, $response->getStatusCode()->getCode());
    }

    public function testDelegatesValidatedPayload() : void
    {
        $payload = ['NotificationType' => 'PlaybackStart', 'ItemType' => 'Movie'];
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['id' => 'secret']);
        $userApi = $this->createStub(UserApi::class);
        $userApi->method('findUserIdByJellyfinWebhookId')->willReturn(42);
        $requestMapper = $this->createMock(JellyfinWebhookRequestMapper::class);
        $requestMapper->expects(self::once())->method('mapRequest')->with($request)->willReturn($payload);
        $scrobbler = $this->createMock(JellyfinScrobbler::class);
        $scrobbler->expects(self::once())->method('processJellyfinWebhook')->with(42, $payload);

        $response = (new JellyfinController($userApi, $scrobbler, $requestMapper))
            ->handleJellyfinWebhook($request);

        self::assertSame(200, $response->getStatusCode()->getCode());
    }
}
