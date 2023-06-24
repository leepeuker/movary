<?php declare(strict_types=1);

namespace Movary\Domain\User;

class UserEntity
{
    private function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly string $passwordHash,
        private readonly bool $isAdmin,
        private readonly ?string $dashboardVisibleRows,
        private readonly ?string $dashboardExtendedRows,
        private readonly ?string $dashboardOrderRows,
        private readonly int $privacyLevel,
        private readonly bool $coreAccountChangesDisabled,
        private readonly int $dateFormatId,
        private readonly ?string $plexWebhookUuid,
        private readonly ?string $jellyfinWebhookUuid,
        private readonly ?string $embyWebhookUuid,
        private readonly ?string $traktUserName,
        private readonly ?string $traktClientId,
        private readonly bool $jellyfinScrobbleWatches,
        private readonly bool $embyScrobbleWatches,
        private readonly bool $plexScrobbleWatches,
        private readonly bool $plexScrobbleRatings,
        private readonly ?string $plexToken,
        private readonly bool $watchlistAutomaticRemovalEnabled,
    ) {
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            (int)$data['id'],
            $data['name'],
            $data['password'],
            (bool)$data['is_admin'],
            $data['dashboard_visible_rows'],
            $data['dashboard_extended_rows'],
            $data['dashboard_order_rows'],
            $data['privacy_level'],
            (bool)$data['core_account_changes_disabled'],
            $data['date_format_id'],
            $data['plex_webhook_uuid'],
            $data['jellyfin_webhook_uuid'],
            $data['emby_webhook_uuid'],
            $data['trakt_user_name'],
            $data['trakt_client_id'],
            (bool)$data['jellyfin_scrobble_views'],
            (bool)$data['emby_scrobble_views'],
            (bool)$data['plex_scrobble_views'],
            (bool)$data['plex_scrobble_ratings'],
            $data['plex_token'],
            (bool)$data['watchlist_automatic_removal_enabled'],
        );
    }

    public function getDashboardExtendedRows() : ?string
    {
        return $this->dashboardExtendedRows;
    }

    public function getDashboardOrderRows() : ?string
    {
        return $this->dashboardOrderRows;
    }

    public function getDashboardVisibleRows() : ?string
    {
        return $this->dashboardVisibleRows;
    }

    public function getDateFormatId() : int
    {
        return $this->dateFormatId;
    }

    public function getEmbyWebhookId() : ?string
    {
        return $this->embyWebhookUuid;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getJellyfinWebhookId() : ?string
    {
        return $this->jellyfinWebhookUuid;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getPasswordHash() : string
    {
        return $this->passwordHash;
    }

    public function getPlexToken() : ?string
    {
        return $this->plexToken;
    }

    public function getPlexWebhookId() : ?string
    {
        return $this->plexWebhookUuid;
    }

    public function getPrivacyLevel() : int
    {
        return $this->privacyLevel;
    }

    public function getTraktClientId() : ?string
    {
        return $this->traktClientId;
    }

    public function getTraktUserName() : ?string
    {
        return $this->traktUserName;
    }

    public function hasCoreAccountChangesDisabled() : bool
    {
        return $this->coreAccountChangesDisabled;
    }

    public function hasEmbyScrobbleWatchesEnabled() : bool
    {
        return $this->embyScrobbleWatches;
    }

    public function hasJellyfinScrobbleWatchesEnabled() : bool
    {
        return $this->jellyfinScrobbleWatches;
    }

    public function hasPlexScrobbleRatingsEnabled() : bool
    {
        return $this->plexScrobbleRatings;
    }

    public function hasPlexScrobbleWatchesEnabled() : bool
    {
        return $this->plexScrobbleWatches;
    }

    public function hasWatchlistAutomaticRemovalEnabled() : bool
    {
        return $this->watchlistAutomaticRemovalEnabled;
    }

    public function isAdmin() : bool
    {
        return $this->isAdmin;
    }
}
