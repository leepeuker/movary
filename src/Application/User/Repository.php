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

    public function createAuthToken(string $token, DateTime $expirationDate) : void
    {
        $this->dbConnection->insert(
            'user_auth_token',
            [
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
