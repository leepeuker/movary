<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\Domain\User\ValueObject\AuthenticatedUser;
use Movary\Domain\User\ValueObject\CredentialType;
use Movary\HttpController\Api\RequestMapper\UserPreferencesRequestMapper;
use Movary\HttpController\Api\UserPreferencesController;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserPreferencesController::class)]
class UserPreferencesControllerTest extends TestCase
{
    public function testUpdatesAuthenticatedUsersPreferences() : void
    {
        $request = $this->createStub(Request::class);
        $authentication = $this->createStub(Authentication::class);
        $authentication->method('authenticateApiToken')->willReturn(AuthenticatedUser::create(7, CredentialType::API_TOKEN));
        $user = $this->createStub(UserEntity::class);
        $user->method('getId')->willReturn(7);
        $userApi = $this->createMock(UserApi::class);
        $userApi->expects(self::once())->method('findUserById')->with(7)->willReturn($user);
        $userApi->expects(self::once())->method('updatePreferences')->with(7, ['privacyLevel' => 2]);
        $requestMapper = $this->createStub(UserPreferencesRequestMapper::class);
        $requestMapper->method('mapUpdate')->willReturn(['privacyLevel' => 2]);

        $response = (new UserPreferencesController($authentication, $userApi, $requestMapper))->updatePreferences($request);

        self::assertSame(204, $response->getStatusCode()->getCode());
    }

    public function testRejectsUnauthenticatedUpdate() : void
    {
        $authentication = $this->createStub(Authentication::class);
        $authentication->method('authenticateApiToken')->willReturn(null);
        $userApi = $this->createMock(UserApi::class);
        $userApi->expects(self::never())->method('updatePreferences');
        $requestMapper = $this->createMock(UserPreferencesRequestMapper::class);
        $requestMapper->expects(self::never())->method('mapUpdate');

        $response = (new UserPreferencesController($authentication, $userApi, $requestMapper))
            ->updatePreferences($this->createStub(Request::class));

        self::assertSame(401, $response->getStatusCode()->getCode());
    }
}
