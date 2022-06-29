<?php declare(strict_types=1);

namespace Movary\Application\User;

use Doctrine\DBAL\Connection;

class Repository
{
    private const ADMIN_USER_IO = 1;

    public function __construct(private readonly Connection $dbConnection)
    {
    }

    public function fetchAdminUser() : Entity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `user` WHERE `id` = ?', [self::ADMIN_USER_IO]);

        if (empty($data) === true) {
            throw new \RuntimeException('Admin user is missing.');
        }

        return Entity::createFromArray($data);
    }

    public function setPlexWebhookId(?string $plexWebhookId) : void
    {
        $this->dbConnection->update(
            'user',
            [
                'plex_webhook_uuid' => $plexWebhookId,
            ],
            [
                'id' => self::ADMIN_USER_IO,
            ]
        );
    }

    public function updateAdminPassword(string $passwordHash) : void
    {
        $this->dbConnection->update(
            'user',
            [
                'password' => $passwordHash,
            ],
            [
                'id' => self::ADMIN_USER_IO,
            ]
        );
    }
}
