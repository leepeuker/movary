<?php declare(strict_types=1);

namespace Tests\Http\Movary;

use InvalidArgumentException;
use Movary\Domain\Movie\History\MovieHistoryEntity;
use Movary\Domain\Movie\History\Location\MovieHistoryLocationApi;
use Movary\Domain\Movie\History\Location\MovieHistoryLocationEntity;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\Domain\Movie\Review\MovieReviewApi;
use Movary\Domain\Movie\Review\MovieReviewEntity;
use Movary\Domain\Movie\Watchlist\MovieWatchlistApi;
use Movary\Domain\User\Service\PersonalApiTokenService;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\ValueObject\Date;
use Psr\Container\ContainerInterface;

class HttpFixture
{
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    /** @return array{user: UserEntity, token: string} */
    public function createUserWithToken(?int $privacyLevel = null, bool $isAdmin = false) : array
    {
        $suffix = bin2hex(random_bytes(8));
        $name = 'HttpTest' . $suffix;

        /** @var UserApi $userApi */
        $userApi = $this->container->get(UserApi::class);
        $userApi->createUser(
            'http-test-' . $suffix . '@example.com',
            'http-test-password',
            $name,
            $isAdmin,
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
        $tokenData = $tokenService->createToken($user->getId(), 'HTTP test', null);

        return ['user' => $user, 'token' => $tokenData['token']];
    }

    public function isValidUserPassword(int $userId, string $password) : bool
    {
        /** @var UserApi $userApi */
        $userApi = $this->container->get(UserApi::class);

        return $userApi->isValidPassword($userId, $password);
    }

    public function createMovie() : MovieEntity
    {
        /** @var MovieApi $movieApi */
        $movieApi = $this->container->get(MovieApi::class);
        $suffix = bin2hex(random_bytes(8));

        return $movieApi->create(
            'HTTP Test Movie ' . $suffix,
            random_int(1, 2_000_000_000),
        );
    }

    public function createLocation(int $userId) : MovieHistoryLocationEntity
    {
        /** @var MovieHistoryLocationApi $locationApi */
        $locationApi = $this->container->get(MovieHistoryLocationApi::class);
        $name = 'HTTP Test Location ' . bin2hex(random_bytes(8));
        $locationApi->createLocation($userId, $name, false);

        return $locationApi->fetchLocationByName($userId, $name);
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

    public function countHistoryPlays(int $movieId, int $userId, ?string $watchedAt) : int
    {
        /** @var \Movary\Domain\Movie\History\MovieHistoryRepository $repository */
        $repository = $this->container->get(\Movary\Domain\Movie\History\MovieHistoryRepository::class);

        return count($repository->fetchPlaysByMovieAndDate(
            $movieId,
            $userId,
            $watchedAt === null ? null : Date::createFromString($watchedAt),
        ));
    }

    public function findMovieReview(int $movieId, int $userId) : ?MovieReviewEntity
    {
        /** @var MovieReviewApi $movieReviewApi */
        $movieReviewApi = $this->container->get(MovieReviewApi::class);

        return $movieReviewApi->find($movieId, $userId);
    }

    public function hasMovieInWatchlist(int $movieId, int $userId) : bool
    {
        /** @var MovieWatchlistApi $movieWatchlistApi */
        $movieWatchlistApi = $this->container->get(MovieWatchlistApi::class);

        return $movieWatchlistApi->hasMovieInWatchlist($userId, $movieId);
    }

    public function addMovieToWatchlist(int $movieId, int $userId) : void
    {
        /** @var MovieWatchlistApi $movieWatchlistApi */
        $movieWatchlistApi = $this->container->get(MovieWatchlistApi::class);
        $movieWatchlistApi->addMovieToWatchlist($userId, $movieId, postToMastodon: false);
    }

    public function createRadarrFeedId(int $userId) : string
    {
        /** @var UserApi $userApi */
        $userApi = $this->container->get(UserApi::class);

        return $userApi->regenerateRadarrFeedId($userId);
    }

    public function createWebhookId(int $userId, string $provider) : string
    {
        /** @var UserApi $userApi */
        $userApi = $this->container->get(UserApi::class);

        return match ($provider) {
            'emby' => $userApi->regenerateEmbyWebhookId($userId),
            'jellyfin' => $userApi->regenerateJellyfinWebhookId($userId),
            'kodi' => $userApi->regenerateKodiWebhookId($userId),
            'plex' => $userApi->regeneratePlexWebhookId($userId),
            default => throw new InvalidArgumentException('Unsupported webhook provider: ' . $provider),
        };
    }
}
