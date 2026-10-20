<?php declare(strict_types=1);

namespace Tests\Api\Movary\Support;

use Movary\Domain\User\Service\PersonalApiTokenService;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Psr\Container\ContainerInterface;

class ApiFixture
{
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    /** @return array{user: UserEntity, token: string} */
    public function createUserWithToken() : array
    {
        $suffix = bin2hex(random_bytes(8));
        $name = 'ApiTest' . $suffix;

        /** @var UserApi $userApi */
        $userApi = $this->container->get(UserApi::class);
        $userApi->createUser(
            'api-test-' . $suffix . '@example.com',
            'api-test-password',
            $name,
            false,
            true,
            true,
        );
        $user = $userApi->fetchUserByName($name);

        /** @var PersonalApiTokenService $tokenService */
        $tokenService = $this->container->get(PersonalApiTokenService::class);
        $tokenData = $tokenService->createToken($user->getId(), 'API test', null);

        return ['user' => $user, 'token' => $tokenData['token']];
    }
}
