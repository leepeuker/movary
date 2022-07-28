<?php declare(strict_types=1);

namespace Movary\Application\User;

use Movary\Application\User\Service\Validator;
use Ramsey\Uuid\Uuid;

class Api
{
    public function __construct(
        private readonly Repository $repository,
        private readonly Validator $userValidator
    ) {
    }

    public function createUser(string $email, string $password, string $name) : void
    {
        $this->userValidator->ensureEmailIsUnique($email);
        $this->userValidator->ensurePasswordIsValid($password);
        $this->userValidator->ensureNameFormatIsValid($name);
        $this->userValidator->ensureNameIsUnique($name);

        $this->repository->createUser($email, password_hash($password, PASSWORD_DEFAULT), $name);
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

    public function fetchAllHavingWatchedMovie(int $movieId) : array
    {
        return $this->repository->fetchAllHavingWatchedMovie($movieId);
    }

    public function fetchAllHavingWatchedMoviesWithPerson(int $personId) : array
    {
        return $this->repository->fetchAllHavingWatchedMovieWithPerson($personId);
    }

    public function fetchDateFormatId(int $userId) : int
    {
        $dateFormat = $this->repository->findDateFormatId($userId);

        if ($dateFormat === null) {
            throw new \RuntimeException('Could not find date format for user.');
        }

        return $dateFormat;
    }

    public function fetchUser(int $userId) : Entity
    {
        $user = $this->repository->findUserById($userId);

        if ($user === null) {
            throw new \RuntimeException('User does not exist with id : ' . $userId);
        }

        return $user;
    }

    public function findPlexWebhookId(int $userId) : ?string
    {
        return $this->repository->findPlexWebhookId($userId);
    }

    public function findTraktClientId(int $userId) : ?string
    {
        return $this->repository->findTraktClientId($userId);
    }

    public function findTraktUserName(int $userId) : ?string
    {
        return $this->repository->findTraktUserName($userId);
    }

    public function findUserByName(string $name) : ?Entity
    {
        return $this->repository->findUserByName($name);
    }

    public function findUserIdByPlexWebhookId(string $webhookId) : ?int
    {
        return $this->repository->findUserIdByPlexWebhookId($webhookId);
    }

    public function isValidPassword(int $userId, string $password) : bool
    {
        $passwordHash = $this->repository->findUserById($userId)?->getPasswordHash();

        if ($passwordHash === null) {
            return false;
        }

        return password_verify($password, $passwordHash) === true;
    }

    public function regeneratePlexWebhookId(int $userId) : string
    {
        $plexWebhookId = Uuid::uuid4()->toString();

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

    public function updateName(int $userId, string $name) : void
    {
        $this->userValidator->ensureNameFormatIsValid($name);
        $this->userValidator->ensureNameIsUnique($name, $userId);

        $this->repository->updateName($userId, $name);
    }

    public function updatePassword(int $userId, string $newPassword) : void
    {
        $this->userValidator->ensurePasswordIsValid($newPassword);

        if ($this->repository->findUserById($userId) === null) {
            throw new \RuntimeException('There is no user with id: ' . $userId);
        }

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        $this->repository->updatePassword($userId, $passwordHash);
    }

    public function updatePrivacyLevel(int $userId, int $privacyLevel) : void
    {
        $this->repository->updatePrivacyLevel($userId, $privacyLevel);
    }

    public function updateTraktClientId(int $userId, ?string $traktClientId) : void
    {
        $this->repository->updateTraktClientId($userId, $traktClientId);
    }

    public function updateTraktUserName(int $userId, ?string $traktUserName) : void
    {
        $this->repository->updateTraktUserName($userId, $traktUserName);
    }
}
