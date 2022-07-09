<?php declare(strict_types=1);

namespace Movary\Application\User;

use Ramsey\Uuid\Uuid;

class Api
{
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

    public function findPlexWebhookIdByUserId(int $userId) : ?string
    {
        return $this->repository->findPlexWebhookIdByUserId($userId);
    }

    public function findUserIdByPlexWebhookId(string $webhookId) : ?int
    {
        return $this->repository->findUserIdByPlexWebhookId($webhookId);
    }

    public function regeneratePlexWebhookId(int $userId) : string
    {
        $plexWebhookId = Uuid::uuid4()->toString();

        $this->repository->setPlexWebhookId($userId, $plexWebhookId);

        return $plexWebhookId;
    }
}
