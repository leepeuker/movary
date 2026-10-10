<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\User\Service;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\Service\CurrentWebUser;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\Domain\User\ValueObject\AuthenticatedUser;
use Movary\Domain\User\ValueObject\CredentialType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CurrentWebUser::class)]
class CurrentWebUserTest extends TestCase
{
    public function testFindUserReturnsNullForMissingSession() : void
    {
        $authentication = $this->createMock(Authentication::class);
        $authentication->expects(self::once())->method('authenticateWebSession')->willReturn(null);
        $userApi = $this->createMock(UserApi::class);
        $userApi->expects(self::never())->method('fetchUser');

        self::assertNull((new CurrentWebUser($authentication, $userApi))->findUser());
    }

    public function testFindUserResolvesAndCachesAuthenticatedUser() : void
    {
        $authenticatedUser = AuthenticatedUser::create(12, CredentialType::WEB_SESSION);
        $user = $this->createStub(UserEntity::class);
        $authentication = $this->createMock(Authentication::class);
        $authentication->expects(self::once())->method('authenticateWebSession')->willReturn($authenticatedUser);
        $userApi = $this->createMock(UserApi::class);
        $userApi->expects(self::once())->method('fetchUser')->with(12)->willReturn($user);
        $subject = new CurrentWebUser($authentication, $userApi);

        self::assertSame($user, $subject->findUser());
        self::assertSame($user, $subject->findUser());
    }

    public function testRequireUserResolvesAndCachesAuthenticatedUser() : void
    {
        $authenticatedUser = AuthenticatedUser::create(12, CredentialType::WEB_SESSION);
        $user = $this->createStub(UserEntity::class);
        $authentication = $this->createMock(Authentication::class);
        $authentication->expects(self::once())->method('requireWebSession')->willReturn($authenticatedUser);
        $userApi = $this->createMock(UserApi::class);
        $userApi->expects(self::once())->method('fetchUser')->with(12)->willReturn($user);
        $subject = new CurrentWebUser($authentication, $userApi);

        self::assertSame($user, $subject->requireUser());
        self::assertSame($user, $subject->requireUser());
    }
}
