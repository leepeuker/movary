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

    public function deletePlexWebhookId() : void
    {
        $this->repository->setPlexWebhookId(null);
    }

    public function findPlexWebhookIdByUserId(int $userId) : ?string
    {
        return $this->repository->findPlexWebhookIdByUserId($userId);
    }

    public function findUserIdByName(string $userName) : ?int
    {
        return $this->repository->findUserIdByName($userName);
    }

    public function findUserIdByPlexWebhookId(string $webhookId) : ?int
    {
        return $this->repository->findUserIdByPlexWebhookId($webhookId);
    }

    public function regeneratePlexWebhookId() : string
    {
        $plexWebhookId = Uuid::uuid4()->toString();

        $this->repository->setPlexWebhookId($plexWebhookId);

        return $plexWebhookId;
    }
}
