<?php declare(strict_types=1);

namespace Movary\Domain\User;

use Doctrine\DBAL\Connection;
use Movary\Api\Jellyfin\Dto\JellyfinAuthenticationData;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\Url;
use RuntimeException;

class UserRepository
{
    public function __construct(private readonly Connection $dbConnection)
    {
    }

    public function createApiToken(int $userId, string $token) : void
    {
        $this->dbConnection->insert(
            'user_api_token',
            [
                'user_id' => $userId,
                'token' => $token,
                'created_at' => (string)DateTime::create(),
            ],
        );
    }

    public function createAuthToken(int $userId, string $token, string $deviceName, string $userAgent, DateTime $expirationDate) : void
    {
        $this->dbConnection->insert(
            'user_auth_token',
            [
                'user_id' => $userId,
                'token' => $token,
                'expiration_date' => (string)$expirationDate,
                'device_name' => $deviceName,
                'user_agent' => $userAgent,
                'created_at' => (string)DateTime::create(),
            ],
        );
    }

    public function createUser(string $email, string $passwordHash, string $name, bool $isAdmin) : void
    {
        $this->dbConnection->insert(
            'user',
            [
                'email' => $email,
                'password' => $passwordHash,
                'is_admin' => (int)$isAdmin,
                'name' => $name,
                'created_at' => (string)DateTime::create(),
            ],
        );
    }

    public function deleteApiToken(int $userId) : void
    {
        $this->dbConnection->delete(
            'user_api_token',
            [
                'user_id' => $userId,
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

    public function deleteJellyfinAuthentication(int $userId) : void
    {
        $this->dbConnection->update(
            'user',
            [
                'jellyfin_access_token' => null,
                'jellyfin_user_id' => null,
            ],
            [
                'id' => $userId,
            ],
        );
    }

    public function deleteUser(int $userId) : void
    {
        $this->dbConnection->delete('user', ['id' => $userId]);
    }

    public function fetchAll() : array
    {
        return $this->dbConnection->fetchAllAssociative('SELECT id, name, email, is_admin as isAdmin FROM `user` ORDER BY id');
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

    public function findApiToken(int $userId) : ?string
    {
        return $this->dbConnection->fetchFirstColumn(
            'SELECT token
            FROM `user_api_token` 
            WHERE user_id = ?',
            [$userId],
        )[0] ?? null;
    }

    public function findAuthTokenExpirationDate(string $token) : ?DateTime
    {
        $expirationDate = $this->dbConnection->fetchOne('SELECT `expiration_date` FROM `user_auth_token` WHERE `token` = ?', [$token]);

        if ($expirationDate === false) {
            return null;
        }

        return DateTime::createFromString($expirationDate);
    }

    public function findJellyfinAuthenticationData(int $userId) : ?array
    {
        $jellyfinAuthenticationData = $this->dbConnection->fetchAllAssociative(
            'SELECT `jellyfin_access_token`, `jellyfin_user_id`, `jellyfin_server_url` 
            FROM `user` WHERE `id` = ?',
            [$userId],
        );

        if (empty($jellyfinAuthenticationData[0]) === true) {
            return null;
        }

        return $jellyfinAuthenticationData[0];
    }

    public function findJellyfinServerUrl(int $userId) : ?string
    {
        $JellyfinServerUrl = $this->dbConnection->fetchOne('SELECT `jellyfin_server_url` FROM `user` WHERE `id` = ?', [$userId]);

        if ($JellyfinServerUrl === false) {
            return null;
        }

        return $JellyfinServerUrl;
    }

    public function findJellyfinUserId(int $userId) : ?string
    {
        $jellyfinUserId = $this->dbConnection->fetchOne('SELECT `jellyfin_user_id` FROM `user` WHERE `id` = ?', [$userId]);

        if ($jellyfinUserId === false) {
            return null;
        }

        return $jellyfinUserId;
    }

    public function findPlexAccessToken(int $userId) : ?string
    {
        $plexAccessToken = $this->dbConnection->fetchOne('SELECT `plex_access_token` FROM `user` WHERE `id` = ?', [$userId]);

        if ($plexAccessToken === false) {
            return null;
        }

        return $plexAccessToken;
    }

    public function findPlexClientId(int $userId) : ?string
    {
        $plexClientId = $this->dbConnection->fetchOne('SELECT `plex_client_id` FROM `user` WHERE `id` = ?', [$userId]);

        if ($plexClientId === false) {
            return null;
        }

        return $plexClientId;
    }

    public function findPlexServerUrl(int $userId) : ?string
    {
        $plexServerUrl = $this->dbConnection->fetchOne('SELECT `plex_server_url` FROM `user` WHERE `id` = ?', [$userId]);

        if ($plexServerUrl === false) {
            return null;
        }

        return $plexServerUrl;
    }

    public function findTOTPUri(int $userId) : ?string
    {
        $totpUri = $this->dbConnection->fetchOne('SELECT `totp_uri` FROM `user` WHERE `id` = ?', [$userId]);

        if ($totpUri === false) {
            return null;
        }

        return $totpUri;
    }

    public function findTemporaryPlexCode(int $userId) : ?string
    {
        $plexCode = $this->dbConnection->fetchOne('SELECT `plex_client_temporary_code` FROM `user` WHERE `id` = ?', [$userId]);

        if ($plexCode === false) {
            return null;
        }

        return $plexCode;
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

    public function findUserByToken(string $apiToken) : ?UserEntity
    {
        $data = $this->dbConnection->fetchAssociative(
            'SELECT user.*
            FROM user
            LEFT JOIN user_api_token ON user.id = user_api_token.user_id
            LEFT JOIN user_auth_token ON user.id = user_auth_token.user_id
            WHERE user_api_token.token = ? OR user_auth_token.token = ?',
            [$apiToken, $apiToken],
        );

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

    public function findUserIdByEmbyWebhookId(string $webhookId) : ?int
    {
        $id = $this->dbConnection->fetchOne('SELECT `id` FROM `user` WHERE `emby_webhook_uuid` = ?', [$webhookId]);

        if ($id === false) {
            return null;
        }

        return (int)$id;
    }

    public function findUserIdByJellyfinWebhookId(string $webhookId) : ?int
    {
        $id = $this->dbConnection->fetchOne('SELECT `id` FROM `user` WHERE `jellyfin_webhook_uuid` = ?', [$webhookId]);

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

    public function findUserIdByRadarrFeedId(string $feedId) : ?int
    {
        $id = $this->dbConnection->fetchOne('SELECT `id` FROM `user` WHERE `radarr_feed_uuid` = ?', [$feedId]);

        if ($id === false) {
            return null;
        }

        return (int)$id;
    }

    public function getCountOfUsers() : int
    {
        $count = $this->dbConnection->fetchOne('SELECT COUNT(*) FROM user');

        if ($count === false) {
            throw new RuntimeException('Could not fetch user count.');
        }

        return $count;
    }

    public function hasHiddenPerson(int $userId, int $personId) : bool
    {
        $userPersonSettings = $this->dbConnection->fetchAllAssociative(
            'SELECT * FROM user_person_settings WHERE user_id = ? AND person_id = ?',
            [$userId, $personId],
        );

        if (isset($userPersonSettings[0]['is_hidden_in_top_lists']) === false) {
            return false;
        }

        return (bool)$userPersonSettings[0]['is_hidden_in_top_lists'];
    }

    public function isLocationsEnabled(int $userId) : bool
    {
        $userPersonSettings = $this->dbConnection->fetchAllAssociative(
            'SELECT locations_enabled FROM user WHERE id = ?',
            [$userId],
        );

        return (bool)$userPersonSettings[0]['locations_enabled'];
    }

    public function setEmbyWebhookId(int $userId, ?string $embyWebhookId) : void
    {
        $this->dbConnection->update(
            'user',
            [
                'emby_webhook_uuid' => $embyWebhookId,
            ],
            [
                'id' => $userId,
            ],
        );
    }

    public function setJellyfinWebhookId(int $userId, ?string $jellyfinWebhookId) : void
    {
        $this->dbConnection->update(
            'user',
            [
                'jellyfin_webhook_uuid' => $jellyfinWebhookId,
            ],
            [
                'id' => $userId,
            ],
        );
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

    public function setRadarrFeedId(int $userId, ?string $radarrFeedId) : void
    {
        $this->dbConnection->update(
            'user',
            [
                'radarr_feed_uuid' => $radarrFeedId,
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

    public function updateCountry(int $userId, ?string $country) : void
    {
        $this->dbConnection->update(
            'user',
            [
                'country' => $country,
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

    public function updateDisplayCharacterNames(int $userId, bool $displayCharacterNames) : void
    {
        $this->dbConnection->update(
            'user',
            [
                'display_character_names' => (int)$displayCharacterNames,
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

    public function updateEmbyScrobblerOptions(int $userId, bool $scrobbleWatches) : void
    {
        $this->dbConnection->update(
            'user',
            [
                'emby_scrobble_views' => (int)$scrobbleWatches,
            ],
            [
                'id' => $userId,
            ],
        );
    }

    public function updateExtendedDashboardRows(int $userId, ?string $extendedRows) : void
    {
        $this->dbConnection->update(
            'user',
            [
                'dashboard_extended_rows' => $extendedRows,
            ],
            [
                'id' => $userId,
            ],
        );
    }

    public function updateIsAdmin(int $userId, bool $isAdmin) : void
    {
        $this->dbConnection->update(
            'user',
            [
                'is_admin' => (int)$isAdmin,
            ],
            [
                'id' => $userId,
            ],
        );
    }

    public function updateJellyfinAuthentication(int $userId, JellyfinAuthenticationData $jellyfinAuthenticationData) : void
    {
        $this->dbConnection->update(
            'user',
            [
                'jellyfin_access_token' => (string)$jellyfinAuthenticationData->getAccessToken(),
                'jellyfin_user_id' => (string)$jellyfinAuthenticationData->getUserId(),
            ],
            [
                'id' => $userId,
            ],
        );
    }

    public function updateJellyfinScrobblerOptions(int $userId, bool $scrobbleWatches) : void
    {
        $this->dbConnection->update(
            'user',
            [
                'jellyfin_scrobble_views' => (int)$scrobbleWatches,
            ],
            [
                'id' => $userId,
            ],
        );
    }

    public function updateJellyfinServerUrl(int $userId, ?Url $serverUrl) : void
    {
        $this->dbConnection->update(
            'user',
            [
                'jellyfin_server_url' => (string)$serverUrl,
            ],
            [
                'id' => $userId,
            ],
        );
    }

    public function updateJellyfinSyncEnabled(int $userId, bool $enabledSync) : void
    {
        $this->dbConnection->update(
            'user',
            [
                'jellyfin_sync_enabled' => (int)$enabledSync,
            ],
            [
                'id' => $userId,
            ],
        );
    }

    public function updateLocationsEnabled(int $userId, bool $locationsEnabled) : void
    {
        $this->dbConnection->update(
            'user',
            [
                'locations_enabled' => (int)$locationsEnabled,
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

    public function updateOrderDashboardRows(int $userId, ?string $orderRows) : void
    {
        $this->dbConnection->update(
            'user',
            [
                'dashboard_order_rows' => $orderRows,
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

    public function updatePlexAccessToken(int $userId, ?string $accessToken) : void
    {
        $this->dbConnection->update(
            'user',
            [
                'plex_access_token' => $accessToken
            ],
            [
                'id' => $userId
            ],
        );
    }

    public function updatePlexAccountId(int $userId, ?string $accountId) : void
    {
        $this->dbConnection->update(
            'user',
            [
                'plex_account_id' => $accountId
            ],
            [
                'id' => $userId
            ],
        );
    }

    public function updatePlexClientId(int $userId, ?int $plexClientId) : void
    {
        $this->dbConnection->update(
            'user',
            [
                'plex_client_id' => $plexClientId
            ],
            [
                'id' => $userId
            ],
        );
    }

    public function updatePlexScrobblerOptions(int $userId, bool $scrobbleWatches, bool $scrobbleRatings) : void
    {
        $this->dbConnection->update(
            'user',
            [
                'plex_scrobble_views' => (int)$scrobbleWatches,
                'plex_scrobble_ratings' => (int)$scrobbleRatings,
            ],
            [
                'id' => $userId,
            ],
        );
    }

    public function updatePlexServerUrl(int $userId, ?Url $plexServerUrl) : void
    {
        $this->dbConnection->update(
            'user',
            [
                'plex_server_url' => (string)$plexServerUrl
            ],
            [
                'id' => $userId
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

    public function updateTemporaryPlexClientCode(int $userId, ?string $plexClientCode) : void
    {
        $this->dbConnection->update(
            'user',
            [
                'plex_client_temporary_code' => $plexClientCode
            ],
            [
                'id' => $userId
            ],
        );
    }

    public function updateTotpUri(int $userId, ?string $totpUri) : void
    {
        $this->dbConnection->update(
            'user',
            [
                'totp_uri' => $totpUri,
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

    public function updateVisibleDashboardRows(int $userId, ?string $visibleRows) : void
    {
        $this->dbConnection->update(
            'user',
            [
                'dashboard_visible_rows' => $visibleRows,
            ],
            [
                'id' => $userId,
            ],
        );
    }

    public function updateWatchlistAutomaticRemovalEnabled(int $userId, bool $watchlistAutomaticRemovalEnabled) : void
    {
        $this->dbConnection->update(
            'user',
            [
                'watchlist_automatic_removal_enabled' => (int)$watchlistAutomaticRemovalEnabled,
            ],
            [
                'id' => $userId,
            ],
        );
    }
}
