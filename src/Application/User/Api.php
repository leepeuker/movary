<?php declare(strict_types=1);

namespace Movary\Application\User;

use Movary\Application\User\Exception\PasswordTooShort;
use Ramsey\Uuid\Uuid;

class Api
{
    private const PASSWORD_MIN_LENGTH = 8;

    public function __construct(private readonly Repository $repository)
    {
    }

    public function createUser(string $email, string $password, ?string $name) : void
    {
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

    public function fetchDateFormatId(int $userId) : int
    {
        $dateFormat = $this->repository->findDateFormatId($userId);

        if ($dateFormat === null) {
            throw new \RuntimeException('Could not find date format for user.');
        }

        return $dateFormat;
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

    public function updateDateFormatId(int $userId, int $dateFormat) : void
    {
        $this->repository->updateDateFormatId($userId, $dateFormat);
    }

    public function updatePassword(int $userId, string $newPassword) : void
    {
        if (strlen($newPassword) < self::PASSWORD_MIN_LENGTH) {
            throw new PasswordTooShort(self::PASSWORD_MIN_LENGTH);
        }

        if ($this->repository->findUserById($userId) === null) {
            throw new \RuntimeException('There is no user with id: ' . $userId);
        }

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        $this->repository->updatePassword($userId, $passwordHash);
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
