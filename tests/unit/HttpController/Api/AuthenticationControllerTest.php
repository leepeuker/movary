<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\Exception\LoginAttemptLimitReached;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\HttpController\Api\AuthenticationController;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\StatusCode;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Movary\HttpController\Api\AuthenticationController::class)]
#[AllowMockObjectsWithoutExpectations]
class AuthenticationControllerTest extends TestCase
{
    private const string TOKEN = 'authentication-token';

    private MockObject|Authentication $authenticationMock;

    private AuthenticationController $subject;

    private MockObject|UserApi $userApiMock;

    protected function setUp() : void
    {
        $this->authenticationMock = $this->createMock(Authentication::class);
        $this->userApiMock = $this->createMock(UserApi::class);
        $this->subject = new AuthenticationController($this->authenticationMock, $this->userApiMock);
    }

    public function testGetTokenDataRejectsInvalidToken() : void
    {
        $request = $this->createMock(Request::class);
        $this->authenticationMock->expects(self::once())->method('getToken')->with($request)->willReturn(self::TOKEN);
        $this->authenticationMock->expects(self::once())->method('getUserIdByToken')->with($request)->willReturn(null);
        $this->userApiMock->expects(self::never())->method('findUserById');

        $response = $this->subject->getTokenData($request);

        self::assertEquals(StatusCode::createUnauthorized(), $response->getStatusCode());
    }

    public function testGetTokenDataReturnsValidatedTokenUser() : void
    {
        $request = $this->createMock(Request::class);
        $user = $this->createMock(UserEntity::class);
        $user->method('getId')->willReturn(12);
        $user->method('getName')->willReturn('example');
        $user->method('isAdmin')->willReturn(false);

        $this->authenticationMock->expects(self::once())->method('getToken')->with($request)->willReturn(self::TOKEN);
        $this->authenticationMock->expects(self::once())->method('getUserIdByToken')->with($request)->willReturn(12);
        $this->userApiMock->expects(self::once())->method('findUserById')->with(12)->willReturn($user);

        $response = $this->subject->getTokenData($request);

        self::assertEquals(StatusCode::createOk(), $response->getStatusCode());
        self::assertSame('{"user":{"id":12,"name":"example","isAdmin":false}}', $response->getBody());
    }

    public function testDestroyTokenRequiresExplicitHeaderToken() : void
    {
        $request = $this->createMock(Request::class);
        $this->authenticationMock
            ->expects(self::once())
            ->method('getTokenFromHeader')
            ->with($request)
            ->willReturn(null);
        $this->authenticationMock->expects(self::never())->method('deleteToken');
        $this->authenticationMock->expects(self::never())->method('logout');

        $response = $this->subject->destroyToken($request);

        self::assertEquals(StatusCode::createBadRequest(), $response->getStatusCode());
    }

    public function testDestroyTokenDeletesExplicitHeaderToken() : void
    {
        $request = $this->createMock(Request::class);
        $this->authenticationMock
            ->expects(self::once())
            ->method('getTokenFromHeader')
            ->with($request)
            ->willReturn(self::TOKEN);
        $this->authenticationMock->expects(self::once())->method('deleteToken')->with(self::TOKEN);
        $this->authenticationMock->expects(self::never())->method('logout');

        $response = $this->subject->destroyToken($request);

        self::assertSame(204, $response->getStatusCode()->getCode());
    }

    public function testCreateTokenReturnsRetryAfterWhenLoginIsRateLimited() : void
    {
        $request = $this->createMock(Request::class);
        $request->method('getBody')->willReturn('{"email":"user@example.com","password":"password"}');
        $request->method('getHeaders')->willReturn(['X-Movary-Client' => 'client']);
        $request->method('getUserAgent')->willReturn('agent');
        $this->authenticationMock
            ->expects(self::once())
            ->method('login')
            ->with('user@example.com', 'password', false, 'client', 'agent', null)
            ->willThrowException(new LoginAttemptLimitReached(60));

        $response = $this->subject->createToken($request);

        self::assertEquals(StatusCode::createTooManyRequests(), $response->getStatusCode());
        self::assertSame('{"error":"InvalidCredentials","message":"Invalid credentials"}', $response->getBody());
        self::assertSame(
            ['Content-Type: application/json', 'Retry-After: 60'],
            array_map(static fn($header) => (string)$header, $response->getHeaders()),
        );
    }
}
