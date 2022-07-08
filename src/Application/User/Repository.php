<?php declare(strict_types=1);

namespace Movary\Application\User;

use Doctrine\DBAL\Connection;
use Movary\ValueObject\DateTime;

class Repository
{
    private const ADMIN_USER_IO = 1;

    public function __construct(private readonly Connection $dbConnection)
    {
    }

    public function createAuthToken(int $userId, string $token, DateTime $expirationDate) : void
    {
        $this->dbConnection->insert(
            'user_auth_token',
            [
                'user_id' => $userId,
                'token' => $token,
                'expiration_date' => (string)$expirationDate,
            ]
        );
    }

    public function deleteAuthToken(string $token) : void
    {
        $this->dbConnection->delete(
            'user_auth_token',
            [
                'token' => $token,
            ]
        );
    }

    public function fetchAdminUser() : Entity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `user` WHERE `id` = ?', [self::ADMIN_USER_IO]);

        if (empty($data) === true) {
            throw new \RuntimeException('Admin user is missing.');
        }

        return Entity::createFromArray($data);
    }

    public function findAuthTokenExpirationDate(string $token) : ?DateTime
    {
        $expirationDate = $this->dbConnection->fetchOne('SELECT `expiration_date` FROM `user_auth_token` WHERE `token` = ?', [$token]);

        if ($expirationDate === false) {
            return null;
        }

        return DateTime::createFromString($expirationDate);
    }

    public function findPlexWebhookIdByUserId(int $userId) : ?string
    {
        $plexWebhookId = $this->dbConnection->fetchOne('SELECT `plex_webhook_uuid` FROM `user` WHERE `id` = ?', [$userId]);

        if ($plexWebhookId === false) {
            return null;
        }

        return $plexWebhookId;
    }

    public function findUserIdByPlexWebhookId(string $webhookId) : ?int
    {
        $id = $this->dbConnection->fetchOne('SELECT `id` FROM `user` WHERE `plex_webhook_uuid` = ?', [$webhookId]);

        if ($id === false) {
            return null;
        }

        return (int)$id;
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
