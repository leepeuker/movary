<?php declare(strict_types=1);

namespace Movary\Application\User;

use Doctrine\DBAL\Connection;
use Movary\ValueObject\DateTime;

class Repository
{
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

    public function createUser(string $email, string $passwordHash, ?string $name) : void
    {
        $this->dbConnection->insert(
            'user',
            [
                'email' => $email,
                'password' => $passwordHash,
                'name' => $name,
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

    public function deleteUser(int $userId) : void
    {
        $this->dbConnection->delete('user', ['id' => $userId]);
    }

    public function findAuthTokenExpirationDate(string $token) : ?DateTime
    {
        $expirationDate = $this->dbConnection->fetchOne('SELECT `expiration_date` FROM `user_auth_token` WHERE `token` = ?', [$token]);

        if ($expirationDate === false) {
            return null;
        }

        return DateTime::createFromString($expirationDate);
    }

    public function findDateFormatId(int $userId) : ?int
    {
        $dateFormat = $this->dbConnection->fetchOne('SELECT `date_format_id` FROM `user` WHERE `id` = ?', [$userId]);

        if ($dateFormat === false) {
            return null;
        }

        return (int)$dateFormat;
    }

    public function findPlexWebhookId(int $userId) : ?string
    {
        $plexWebhookId = $this->dbConnection->fetchOne('SELECT `plex_webhook_uuid` FROM `user` WHERE `id` = ?', [$userId]);

        if ($plexWebhookId === false) {
            return null;
        }

        return $plexWebhookId;
    }

    public function findTraktClientId(int $userId) : ?string
    {
        $plexWebhookId = $this->dbConnection->fetchOne('SELECT `trakt_client_id` FROM `user` WHERE `id` = ?', [$userId]);

        if ($plexWebhookId === false) {
            return null;
        }

        return $plexWebhookId;
    }

    public function findTraktUserName(int $userId) : ?string
    {
        $plexWebhookId = $this->dbConnection->fetchOne('SELECT `trakt_user_name` FROM `user` WHERE `id` = ?', [$userId]);

        if ($plexWebhookId === false) {
            return null;
        }

        return $plexWebhookId;
    }

    public function findUserByEmail(string $email) : ?Entity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `user` WHERE `email` = ?', [$email]);

        if (empty($data) === true) {
            return null;
        }

        return Entity::createFromArray($data);
    }

    public function findUserById(int $userId) : ?Entity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `user` WHERE `id` = ?', [$userId]);

        if (empty($data) === true) {
            return null;
        }

        return Entity::createFromArray($data);
    }

    public function findUserIdByAuthToken(string $token) : ?int
    {
        $id = $this->dbConnection->fetchOne('SELECT `user_id` FROM `user_auth_token` WHERE `token` = ?', [$token]);

        if ($id === false) {
            return null;
        }

        return (int)$id;
    }

    public function findUserIdByPlexWebhookId(string $webhookId) : ?int
    {
        $id = $this->dbConnection->fetchOne('SELECT `id` FROM `user` WHERE `plex_webhook_uuid` = ?', [$webhookId]);

        if ($id === false) {
            return null;
        }

        return (int)$id;
    }

    public function setPlexWebhookId(int $userId, ?string $plexWebhookId) : void
    {
        $this->dbConnection->update(
            'user',
            [
                'plex_webhook_uuid' => $plexWebhookId,
            ],
            [
                'id' => $userId,
            ]
        );
    }

    public function updateDateFormatId(int $userId, int $dateFormat) : void
    {
        $this->dbConnection->update(
            'user',
            [
                'date_format_id' => $dateFormat,
            ],
            [
                'id' => $userId,
            ]
        );
    }

    public function updateEmail(int $userId, string $email) : void
    {
        $this->dbConnection->update(
            'user',
            [
                'email' => $email,
            ],
            [
                'id' => $userId,
            ]
        );
    }

    public function updatePassword(int $userId, string $passwordHash) : void
    {
        $this->dbConnection->update(
            'user',
            [
                'password' => $passwordHash,
            ],
            [
                'id' => $userId,
            ]
        );
    }

    public function updateTraktClientId(int $userId, ?string $traktClientId) : void
    {
        $this->dbConnection->update(
            'user',
            [
                'trakt_client_id' => $traktClientId,
            ],
            [
                'id' => $userId,
            ]
        );
    }

    public function updateTraktUserName(int $userId, ?string $traktUserName) : void
    {
        $this->dbConnection->update(
            'user',
            [
                'trakt_user_name' => $traktUserName,
            ],
            [
                'id' => $userId,
            ]
        );
    }
}
