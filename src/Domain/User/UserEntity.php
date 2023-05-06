<?php declare(strict_types=1);

namespace Movary\Domain\User;

class UserEntity
{
    private function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly string $passwordHash,
        private readonly bool $isAdmin,
        private readonly int $privacyLevel,
        private readonly bool $coreAccountChangesDisabled,
        private readonly int $dateFormatId,
        private readonly ?string $plexWebhookUuid,
        private readonly ?string $traktUserName,
        private readonly ?string $traktClientId,
        private readonly bool $jellyfinScrobbleWatches,
        private readonly bool $plexScrobbleWatches,
        private readonly bool $plexScrobbleRatings,
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
            $data['privacy_level'],
            (bool)$data['core_account_changes_disabled'],
            $data['date_format_id'],
            $data['plex_webhook_uuid'],
            $data['trakt_user_name'],
            $data['trakt_client_id'],
            (bool)$data['jellyfin_scrobble_views'],
            (bool)$data['plex_scrobble_views'],
            (bool)$data['plex_scrobble_ratings'],
            (bool)$data['watchlist_automatic_removal_enabled'],
        );
    }

    public function getDateFormatId() : int
    {
        return $this->dateFormatId;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getJellyfinWebhookId() : ?string
    {
        return $this->plexWebhookUuid;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getPasswordHash() : string
    {
        return $this->passwordHash;
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
