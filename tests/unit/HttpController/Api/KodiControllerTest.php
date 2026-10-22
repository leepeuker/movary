<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api;

use Movary\Domain\User\UserApi;
use Movary\HttpController\Api\KodiController;
use Movary\HttpController\Api\RequestMapper\KodiWebhookRequestMapper;
use Movary\Service\Kodi\KodiScrobbler;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(KodiController::class)]
class KodiControllerTest extends TestCase
{
    public function testReturnsNotFoundBeforeMappingPayloadForUnknownWebhook() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['token' => 'secret']);
        $userApi = $this->createStub(UserApi::class);
        $userApi->method('findUserIdByKodiWebhookId')->willReturn(null);
        $requestMapper = $this->createMock(KodiWebhookRequestMapper::class);
        $requestMapper->expects(self::never())->method('mapRequest');

        $response = (new KodiController(
            $userApi,
            $this->createStub(KodiScrobbler::class),
            $requestMapper,
        ))->handleKodiWebhook($request);

        self::assertSame(404, $response->getStatusCode()->getCode());
    }

    public function testDelegatesValidatedPayload() : void
    {
        $payload = ['title' => 'Movie', 'uniqueIds' => ['tmdbId' => '42']];
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['token' => 'secret']);
        $userApi = $this->createStub(UserApi::class);
        $userApi->method('findUserIdByKodiWebhookId')->willReturn(42);
        $requestMapper = $this->createMock(KodiWebhookRequestMapper::class);
        $requestMapper->expects(self::once())->method('mapRequest')->with($request)->willReturn($payload);
        $scrobbler = $this->createMock(KodiScrobbler::class);
        $scrobbler->expects(self::once())->method('processKodiWebhook')->with(42, $payload);

        $response = (new KodiController($userApi, $scrobbler, $requestMapper))->handleKodiWebhook($request);

        self::assertSame(200, $response->getStatusCode()->getCode());
    }
}
