<?php declare(strict_types=1);

namespace Tests\Api\Movary\Support;

use Movary\Domain\Movie\History\MovieHistoryEntity;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\Domain\User\Service\PersonalApiTokenService;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\ValueObject\Date;
use Psr\Container\ContainerInterface;

class ApiFixture
{
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    /** @return array{user: UserEntity, token: string} */
    public function createUserWithToken(?int $privacyLevel = null) : array
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
        if ($privacyLevel !== null) {
            $userApi->updatePrivacyLevel($user->getId(), $privacyLevel);
            $user = $userApi->fetchUser($user->getId());
        }

        /** @var PersonalApiTokenService $tokenService */
        $tokenService = $this->container->get(PersonalApiTokenService::class);
        $tokenData = $tokenService->createToken($user->getId(), 'API test', null);

        return ['user' => $user, 'token' => $tokenData['token']];
    }

    public function createMovie() : MovieEntity
    {
        /** @var MovieApi $movieApi */
        $movieApi = $this->container->get(MovieApi::class);
        $suffix = bin2hex(random_bytes(8));

        return $movieApi->create(
            'API Test Movie ' . $suffix,
            random_int(1, 2_000_000_000),
        );
    }

    public function findHistoryEntry(
        int $movieId,
        int $userId,
        ?string $watchedAt,
    ) : ?MovieHistoryEntity {
        /** @var MovieApi $movieApi */
        $movieApi = $this->container->get(MovieApi::class);

        return $movieApi->findHistoryEntryForMovieByUserOnDate(
            $movieId,
            $userId,
            $watchedAt === null ? null : Date::createFromString($watchedAt),
        );
    }
}
