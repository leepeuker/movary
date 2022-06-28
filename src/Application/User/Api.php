<?php declare(strict_types=1);

namespace Movary\Application\User;

use Ramsey\Uuid\Uuid;

class Api
{
    public function __construct(private readonly Repository $repository)
    {
    }

    public function findPlexWebhookId() : ?string
    {
        return $this->repository->fetchAdminUser()->getPlexWebhookId();
    }

    public function regeneratePlexWebhookId() : string
    {
        $plexWebhookId = Uuid::uuid4()->toString();

        $this->repository->setPlexWebhookId($plexWebhookId);

        return $plexWebhookId;
    }
}
