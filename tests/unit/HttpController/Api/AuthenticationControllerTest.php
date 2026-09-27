<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api;

use Movary\Domain\User\Service\Authentication;
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
}
