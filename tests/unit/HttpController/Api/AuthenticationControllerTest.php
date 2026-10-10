<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\Domain\User\ValueObject\AuthenticatedUser;
use Movary\Domain\User\ValueObject\CredentialType;
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
        $request->expects(self::once())->method('getHeader')->with('X-Movary-Token')->willReturn(self::TOKEN);
        $this->authenticationMock->expects(self::once())->method('authenticateApiToken')->with($request)->willReturn(null);
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

        $request->expects(self::once())->method('getHeader')->with('X-Movary-Token')->willReturn(self::TOKEN);
        $this->authenticationMock
            ->expects(self::once())
            ->method('authenticateApiToken')
            ->with($request)
            ->willReturn(AuthenticatedUser::create(12, CredentialType::API_TOKEN));
        $this->userApiMock->expects(self::once())->method('findUserById')->with(12)->willReturn($user);

        $response = $this->subject->getTokenData($request);

        self::assertEquals(StatusCode::createOk(), $response->getStatusCode());
        self::assertSame('{"user":{"id":12,"name":"example","isAdmin":false}}', $response->getBody());
    }

    public function testGetTokenDataRejectsMissingToken() : void
    {
        $request = $this->createMock(Request::class);
        $request->expects(self::once())->method('getHeader')->with('X-Movary-Token')->willReturn(null);
        $this->authenticationMock->expects(self::never())->method('authenticateApiToken');
        $this->userApiMock->expects(self::never())->method('findUserById');

        $response = $this->subject->getTokenData($request);

        self::assertEquals(StatusCode::createBadRequest(), $response->getStatusCode());
    }
}
