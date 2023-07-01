<?php declare(strict_types=1);

namespace Movary\Domain\User;

use Movary\Api\Plex\Dto\PlexAccessToken;
use Movary\Domain\User\Service\Validator;
use Movary\ValueObject\Url;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class UserApi
{
    public function __construct(
        private readonly UserRepository $repository,
        private readonly Validator $userValidator,
    ) {
    }

    public function createUser(string $email, string $password, string $name, bool $isAdmin = false) : void
    {
        $this->userValidator->ensureEmailIsUnique($email);
        $this->userValidator->ensurePasswordIsValid($password);
        $this->userValidator->ensureNameFormatIsValid($name);
        $this->userValidator->ensureNameIsUnique($name);

        $this->repository->createUser($email, password_hash($password, PASSWORD_DEFAULT), $name, $isAdmin);
    }

    public function deleteEmbyWebhookId(int $userId) : void
    {
        $this->repository->setEmbyWebhookId($userId, null);
    }

    public function deleteJellyfinWebhookId(int $userId) : void
    {
        $this->repository->setJellyfinWebhookId($userId, null);
    }

    public function deletePlexWebhookId(int $userId) : void
    {
        $this->repository->setPlexWebhookId($userId, null);
    }

    public function deleteUser(int $userId) : void
    {
        $this->repository->deleteUser($userId);
    }

    public function fetchAll() : array
    {
        return $this->repository->fetchAll();
    }

    public function fetchAllHavingWatchedMovieInternVisibleUsernames(int $movieId) : array
    {
        return $this->repository->fetchAllHavingWatchedMovieInternVisibleUsernames($movieId);
    }

    public function fetchAllHavingWatchedMoviePublicVisibleUsernames(int $movieId) : array
    {
        return $this->repository->fetchAllHavingWatchedMoviePublicVisibleUsernames($movieId);
    }

    public function fetchAllHavingWatchedMovieWithPersonInternVisibleUsernames(int $personId) : array
    {
        return $this->repository->fetchAllHavingWatchedMovieWithPersonInternVisibleUsernames($personId);
    }

    public function fetchAllHavingWatchedMovieWithPersonPublicVisibleUsernames(int $personId) : array
    {
        return $this->repository->fetchAllHavingWatchedMovieWithPersonPublicVisibleUsernames($personId);
    }

    public function fetchAllInternVisibleUsernames() : array
    {
        return $this->repository->fetchAllInternVisibleUsernames();
    }

    public function fetchAllPublicVisibleUsernames() : array
    {
        return $this->repository->fetchAllPublicVisibleUsernames();
    }

    public function fetchUser(int $userId) : UserEntity
    {
        $user = $this->repository->findUserById($userId);

        if ($user === null) {
            throw new RuntimeException('User does not exist with id : ' . $userId);
        }

        return $user;
    }

    public function findPlexAccessToken(int $userId) : ?PlexAccessToken
    {
        $plexAccessToken = $this->repository->findPlexAccessToken($userId);

        if ($plexAccessToken === null) {
            return null;
        }

        return PlexAccessToken::create($plexAccessToken);
    }

    public function findPlexClientId(int $userId) : ?string
    {
        return $this->repository->findPlexClientId($userId);
    }

    public function findPlexServerUrl(int $userId) : ?Url
    {
        $url = $this->repository->findPlexServerUrl($userId);

        return $url !== null ? Url::createFromString($url) : null;
    }

    public function findTemporaryPlexCode(int $userId) : ?string
    {
        return $this->repository->findTemporaryPlexCode($userId);
    }

    public function findTraktClientId(int $userId) : ?string
    {
        return $this->repository->findTraktClientId($userId);
    }

    public function findTraktUserName(int $userId) : ?string
    {
        return $this->repository->findTraktUserName($userId);
    }

    public function findUserByName(string $name) : ?UserEntity
    {
        return $this->repository->findUserByName($name);
    }

    public function findUserIdByEmbyWebhookId(string $webhookId) : ?int
    {
        return $this->repository->findUserIdByEmbyWebhookId($webhookId);
    }

    public function findUserIdByJellyfinWebhookId(string $webhookId) : ?int
    {
        return $this->repository->findUserIdByJellyfinWebhookId($webhookId);
    }

    public function findUserIdByPlexWebhookId(string $webhookId) : ?int
    {
        return $this->repository->findUserIdByPlexWebhookId($webhookId);
    }

    public function hasUsers() : bool
    {
        return $this->repository->getCountOfUsers() > 0;
    }

    public function isValidPassword(int $userId, string $password) : bool
    {
        $passwordHash = $this->repository->findUserById($userId)?->getPasswordHash();

        if ($passwordHash === null) {
            return false;
        }

        return password_verify($password, $passwordHash) === true;
    }

    public function regenerateEmbyWebhookId(int $userId) : string
    {
        $jellyfinWebhookId = (string)Uuid::uuid4();

        $this->repository->setEmbyWebhookId($userId, $jellyfinWebhookId);

        return $jellyfinWebhookId;
    }

    public function regenerateJellyfinWebhookId(int $userId) : string
    {
        $jellyfinWebhookId = (string)Uuid::uuid4();

        $this->repository->setJellyfinWebhookId($userId, $jellyfinWebhookId);

        return $jellyfinWebhookId;
    }

    public function regeneratePlexWebhookId(int $userId) : string
    {
        $plexWebhookId = (string)Uuid::uuid4();

        $this->repository->setPlexWebhookId($userId, $plexWebhookId);

        return $plexWebhookId;
    }

    public function updateCoreAccountChangesDisabled(int $userId, bool $updateCoreAccountChangesDisabled) : void
    {
        $this->repository->updateCoreAccountChangesDisabled($userId, $updateCoreAccountChangesDisabled);
    }

    public function updateDateFormatId(int $userId, int $dateFormat) : void
    {
        $this->repository->updateDateFormatId($userId, $dateFormat);
    }

    public function updateEmail(int $userId, string $email) : void
    {
        $this->userValidator->ensureEmailIsUnique($email, $userId);

        $this->repository->updateEmail($userId, $email);
    }

    public function updateEmbyScrobblerOptions(int $userId, bool $scrobbleWatches) : void
    {
        $this->repository->updateEmbyScrobblerOptions($userId, $scrobbleWatches);
    }

    public function updateExtendedDashboardRows(int $userId, ?string $extendedRows) : void
    {
        if ($this->repository->findUserById($userId) === null) {
            throw new RuntimeException('There is no user with id: ' . $userId);
        }

        $this->repository->updateExtendedDashboardRows($userId, $extendedRows);
    }

    public function updateIsAdmin(int $userId, bool $isAdmin) : void
    {
        $this->repository->updateIsAdmin($userId, $isAdmin);
    }

    public function updateJellyfinScrobblerOptions(int $userId, bool $scrobbleWatches) : void
    {
        $this->repository->updateJellyfinScrobblerOptions($userId, $scrobbleWatches);
    }

    public function updateName(int $userId, string $name) : void
    {
        $this->userValidator->ensureNameFormatIsValid($name);
        $this->userValidator->ensureNameIsUnique($name, $userId);

        $this->repository->updateName($userId, $name);
    }

    public function updateOrderDashboardRows(int $userId, ?string $orderRows) : void
    {
        if ($this->repository->findUserById($userId) === null) {
            throw new RuntimeException('There is no user with id: ' . $userId);
        }

        $this->repository->updateOrderDashboardRows($userId, $orderRows);
    }

    public function updatePassword(int $userId, string $newPassword) : void
    {
        $this->userValidator->ensurePasswordIsValid($newPassword);

        if ($this->repository->findUserById($userId) === null) {
            throw new RuntimeException('There is no user with id: ' . $userId);
        }

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        $this->repository->updatePassword($userId, $passwordHash);
    }

    public function updatePlexAccessToken(int $userId, ?string $plexAccessToken) : void
    {
        $this->repository->updatePlexAccessToken($userId, $plexAccessToken);
    }

    public function updatePlexAccountId(int $userId, ?string $plexAccountId) : void
    {
        $this->repository->updatePlexAccountId($userId, $plexAccountId);
    }

    public function updatePlexClientId(int $userId, ?int $plexClientId) : void
    {
        $this->repository->updatePlexClientId($userId, $plexClientId);
    }

    public function updatePlexScrobblerOptions(int $userId, bool $scrobbleWatches, bool $scrobbleRatings) : void
    {
        $this->repository->updatePlexScrobblerOptions($userId, $scrobbleWatches, $scrobbleRatings);
    }

    public function updatePlexServerUrl(int $userId, ?Url $plexServerUrl) : void
    {
        $this->repository->updatePlexServerurl($userId, $plexServerUrl);
    }

    public function updatePrivacyLevel(int $userId, int $privacyLevel) : void
    {
        $this->repository->updatePrivacyLevel($userId, $privacyLevel);
    }

    public function updateTemporaryPlexClientCode(int $userId, ?string $plexClientCode) : void
    {
        $this->repository->updateTemporaryPlexClientCode($userId, $plexClientCode);
    }

    public function updateTraktClientId(int $userId, ?string $traktClientId) : void
    {
        $this->repository->updateTraktClientId($userId, $traktClientId);
    }

    public function updateTraktUserName(int $userId, ?string $traktUserName) : void
    {
        $this->repository->updateTraktUserName($userId, $traktUserName);
    }

    public function updateVisibleDashboardRows(int $userId, ?string $visibleRows) : void
    {
        if ($this->repository->findUserById($userId) === null) {
            throw new RuntimeException('There is no user with id: ' . $userId);
        }

        $this->repository->updateVisibleDashboardRows($userId, $visibleRows);
    }

    public function updateWatchlistAutomaticRemovalEnabled(int $userId, bool $watchlistAutomaticRemovalEnabled) : void
    {
        $this->repository->updateWatchlistAutomaticRemovalEnabled($userId, $watchlistAutomaticRemovalEnabled);
    }
}
