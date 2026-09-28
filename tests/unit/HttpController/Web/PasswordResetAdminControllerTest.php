<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web;

use Movary\Domain\User\Service\PasswordResetRequestService;
use Movary\Domain\User\Service\PasswordResetTokenService;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\HttpController\Web\PasswordResetAdminController;
use Movary\Util\Json;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\StatusCode;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(PasswordResetAdminController::class)]
#[AllowMockObjectsWithoutExpectations]
class PasswordResetAdminControllerTest extends TestCase
{
    private PasswordResetRequestService|MockObject $requestServiceMock;

    private PasswordResetAdminController $subject;

    private PasswordResetTokenService|MockObject $tokenServiceMock;

    private UserApi|MockObject $userApiMock;

    protected function setUp() : void
    {
        $this->userApiMock = $this->createMock(UserApi::class);
        $this->requestServiceMock = $this->createMock(PasswordResetRequestService::class);
        $this->tokenServiceMock = $this->createMock(PasswordResetTokenService::class);
        $this->subject = new PasswordResetAdminController(
            $this->userApiMock,
            $this->requestServiceMock,
            $this->tokenServiceMock,
        );
    }

    public function testFetchPendingResetsReturnsMetadataWithoutSecrets() : void
    {
        $this->tokenServiceMock
            ->expects(self::once())
            ->method('fetchPendingTokens')
            ->willReturn([[
                'userId' => 12,
                'name' => 'Alice',
                'email' => 'alice@example.com',
                'expirationDate' => DateTime::createFromString('2026-09-28 12:15:00'),
                'createdAt' => DateTime::createFromString('2026-09-28 12:00:00'),
            ]]);

        $response = $this->subject->fetchPendingResets();
        $data = Json::decode((string)$response->getBody());

        self::assertEquals(StatusCode::createOk(), $response->getStatusCode());
        self::assertSame(12, $data[0]['userId']);
        self::assertArrayNotHasKey('token', $data[0]);
        self::assertArrayNotHasKey('tokenHash', $data[0]);
    }

    public function testCreateResetSendsEmailForUser() : void
    {
        $request = $this->createRequest(12);
        $user = $this->createUser(false);
        $this->userApiMock
            ->expects(self::once())
            ->method('findUserById')
            ->with(12)
            ->willReturn($user);
        $this->requestServiceMock
            ->expects(self::once())
            ->method('requestForUser')
            ->with($user)
            ->willReturn(true);

        self::assertEquals(StatusCode::createOk(), $this->subject->createReset($request)->getStatusCode());
    }

    public function testCreateResetRejectsUnknownUser() : void
    {
        $this->userApiMock->method('findUserById')->willReturn(null);
        $this->requestServiceMock->expects(self::never())->method('requestForUser');

        self::assertEquals(
            StatusCode::createNotFound(),
            $this->subject->createReset($this->createRequest(99))->getStatusCode(),
        );
    }

    public function testCreateResetRejectsProtectedUser() : void
    {
        $this->userApiMock->method('findUserById')->willReturn($this->createUser(true));
        $this->requestServiceMock->expects(self::never())->method('requestForUser');

        $response = $this->subject->createReset($this->createRequest(12));

        self::assertEquals(StatusCode::createBadRequest(), $response->getStatusCode());
        self::assertSame('Password resets are disabled for this user.', $response->getBody());
    }

    public function testCreateResetReportsSchedulingFailure() : void
    {
        $user = $this->createUser(false);
        $this->userApiMock->method('findUserById')->willReturn($user);
        $this->requestServiceMock->method('requestForUser')->willReturn(false);

        $response = $this->subject->createReset($this->createRequest(12));

        self::assertEquals(StatusCode::createBadRequest(), $response->getStatusCode());
        self::assertSame('Could not schedule password reset email.', $response->getBody());
    }

    public function testRevokeAllResetsDeletesAllTokens() : void
    {
        $this->tokenServiceMock
            ->expects(self::once())
            ->method('deleteAllTokens');

        self::assertSame(204, $this->subject->revokeAllResets()->getStatusCode()->getCode());
    }

    public function testRevokeResetDeletesUserToken() : void
    {
        $this->userApiMock->method('findUserById')->willReturn($this->createUser(false));
        $this->tokenServiceMock
            ->expects(self::once())
            ->method('deleteTokenForUser')
            ->with(12);

        self::assertEquals(
            204,
            $this->subject->revokeReset($this->createRequest(12))->getStatusCode()->getCode(),
        );
    }

    public function testRevokeResetRejectsUnknownUser() : void
    {
        $this->userApiMock->method('findUserById')->willReturn(null);
        $this->tokenServiceMock->expects(self::never())->method('deleteTokenForUser');

        self::assertEquals(
            StatusCode::createNotFound(),
            $this->subject->revokeReset($this->createRequest(99))->getStatusCode(),
        );
    }

    private function createRequest(int $userId) : Request&MockObject
    {
        $request = $this->createMock(Request::class);
        $request->method('getRouteParameters')->willReturn(['userId' => (string)$userId]);

        return $request;
    }

    private function createUser(bool $accountChangesDisabled) : UserEntity&MockObject
    {
        $user = $this->createMock(UserEntity::class);
        $user->method('hasCoreAccountChangesDisabled')->willReturn($accountChangesDisabled);

        return $user;
    }
}
