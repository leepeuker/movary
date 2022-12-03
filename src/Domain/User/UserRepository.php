<?php declare(strict_types=1);

namespace Movary\Domain\User;

use Doctrine\DBAL\Connection;
use Movary\ValueObject\DateTime;

class UserRepository
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
            ],
        );
    }

    public function createUser(string $email, string $passwordHash, string $name) : void
    {
        $this->dbConnection->insert(
            'user',
            [
                'email' => $email,
                'password' => $passwordHash,
                'name' => $name,
            ],
        );
    }

    public function deleteAuthToken(string $token) : void
    {
        $this->dbConnection->delete(
            'user_auth_token',
            [
                'token' => $token,
            ],
        );
    }

    public function deleteUser(int $userId) : void
    {
        $this->dbConnection->delete('user', ['id' => $userId]);
    }

    public function fetchAll() : array
    {
        return $this->dbConnection->fetchAllAssociative('SELECT * FROM `user` ORDER BY name');
    }

    public function fetchAllHavingWatchedMovieInternVisibleUsernames(int $movieId) : array
    {
        return $this->dbConnection->fetchAllAssociative(
            'SELECT DISTINCT user.name 
            FROM `user` 
            JOIN movie_user_watch_dates muwd ON user.id = muwd.user_id AND user.privacy_level >= 1
            WHERE movie_id = ?
            ORDER BY name',
            [$movieId],
        );
    }

    public function fetchAllHavingWatchedMoviePublicVisibleUsernames(int $movieId) : array
    {
        return $this->dbConnection->fetchAllAssociative(
            'SELECT DISTINCT user.name 
            FROM `user` 
            JOIN movie_user_watch_dates muwd ON user.id = muwd.user_id AND user.privacy_level >= 2
            WHERE movie_id = ?
            ORDER BY name',
            [$movieId],
        );
    }

    public function fetchAllHavingWatchedMovieWithPerson(int $personId) : array
    {
        return $this->dbConnection->fetchAllAssociative(
            'SELECT DISTINCT user.name
            FROM `user` 
            JOIN movie_user_watch_dates muwd ON user.id = muwd.user_id 
            JOIN movie_cast mc ON muwd.movie_id = mc.movie_id 
            WHERE person_id = ?
            ORDER BY name',
            [$personId],
        );
    }

    public function fetchAllHavingWatchedMovieWithPersonInternVisibleUsernames(int $personId) : array
    {
        return $this->dbConnection->fetchAllAssociative(
            'SELECT DISTINCT user.name
            FROM `user` 
            JOIN movie_user_watch_dates muwd ON user.id = muwd.user_id 
            JOIN movie_cast mc ON muwd.movie_id = mc.movie_id AND user.privacy_level >= 1
            WHERE person_id = ?
            ORDER BY name',
            [$personId],
        );
    }

    public function fetchAllHavingWatchedMovieWithPersonPublicVisibleUsernames(int $personId) : array
    {
        return $this->dbConnection->fetchAllAssociative(
            'SELECT DISTINCT user.name
            FROM `user` 
            JOIN movie_user_watch_dates muwd ON user.id = muwd.user_id 
            JOIN movie_cast mc ON muwd.movie_id = mc.movie_id AND user.privacy_level >= 2
            WHERE person_id = ?
            ORDER BY name',
            [$personId],
        );
    }

    public function fetchAllInternVisibleUsernames() : array
    {
        return $this->dbConnection->fetchAllAssociative(
            'SELECT DISTINCT user.name
            FROM `user` 
            WHERE privacy_level >= 1
            ORDER BY name',
        );
    }

    public function fetchAllPublicVisibleUsernames() : array
    {
        return $this->dbConnection->fetchAllAssociative(
            'SELECT DISTINCT user.name
            FROM `user` 
            WHERE privacy_level >= 2
            ORDER BY name',
        );
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

    public function findUserByEmail(string $email) : ?UserEntity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `user` WHERE `email` = ?', [$email]);

        if (empty($data) === true) {
            return null;
        }

        return UserEntity::createFromArray($data);
    }

    public function findUserById(int $userId) : ?UserEntity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `user` WHERE `id` = ?', [$userId]);

        if (empty($data) === true) {
            return null;
        }

        return UserEntity::createFromArray($data);
    }

    public function findUserByName(string $name) : ?UserEntity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `user` WHERE `name` = ?', [$name]);

        if (empty($data) === true) {
            return null;
        }

        return UserEntity::createFromArray($data);
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

    public function getCountOfUsers() : int
    {
        $count = $this->dbConnection->fetchOne('SELECT COUNT(*) FROM user');

        if ($count === false) {
            throw new \RuntimeException('Could not fetch user count.');
        }

        return $count;
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
            ],
        );
    }

    public function updateCoreAccountChangesDisabled(int $userId, bool $coreAccountChangesDisabled) : void
    {
        $this->dbConnection->update(
            'user',
            [
                'core_account_changes_disabled' => $coreAccountChangesDisabled === true ? 1 : 0,
            ],
            [
                'id' => $userId,
            ],
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
            ],
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
            ],
        );
    }

    public function updateName(int $userId, string $name) : void
    {
        $this->dbConnection->update(
            'user',
            [
                'name' => $name,
            ],
            [
                'id' => $userId,
            ],
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
            ],
        );
    }

    public function updatePrivacyLevel(int $userId, int $privacyLevel) : void
    {
        $this->dbConnection->update(
            'user',
            [
                'privacy_level' => $privacyLevel,
            ],
            [
                'id' => $userId,
            ],
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
            ],
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
            ],
        );
    }
}
