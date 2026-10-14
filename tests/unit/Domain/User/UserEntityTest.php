<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\User;

use Movary\Domain\User\UserEntity;
use Movary\ValueObject\DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserEntity::class)]
class UserEntityTest extends TestCase
{
    public function testCreatesVerifiedUserFromArray() : void
    {
        $user = UserEntity::createFromArray($this->createUserData('2026-10-14 20:00:00'));

        self::assertTrue($user->isEmailVerified());
        self::assertEquals(
            DateTime::createFromString('2026-10-14 20:00:00'),
            $user->getEmailVerifiedAt(),
        );
    }

    public function testCreatesUnverifiedUserFromArray() : void
    {
        $user = UserEntity::createFromArray($this->createUserData(null));

        self::assertFalse($user->isEmailVerified());
        self::assertNull($user->getEmailVerifiedAt());
    }

    private function createUserData(?string $emailVerifiedAt) : array
    {
        return [
            'id' => 7,
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'email_verified_at' => $emailVerifiedAt,
            'password' => 'password-hash',
            'is_admin' => 0,
            'is_enabled' => 1,
            'dashboard_visible_rows' => null,
            'dashboard_extended_rows' => null,
            'dashboard_order_rows' => null,
            'privacy_level' => 1,
            'core_account_changes_disabled' => 0,
            'date_format_id' => 0,
            'plex_webhook_uuid' => null,
            'jellyfin_webhook_uuid' => null,
            'emby_webhook_uuid' => null,
            'kodi_webhook_uuid' => null,
            'trakt_user_name' => null,
            'trakt_client_id' => null,
            'plex_access_token' => null,
            'plex_server_url' => null,
            'jellyfin_scrobble_views' => 1,
            'emby_scrobble_views' => 1,
            'kodi_scrobble_views' => 1,
            'plex_scrobble_views' => 1,
            'plex_scrobble_ratings' => 0,
            'radarr_feed_uuid' => null,
            'watchlist_automatic_removal_enabled' => 1,
            'country' => null,
            'jellyfin_sync_enabled' => 0,
            'display_character_names' => 1,
            'locations_enabled' => 1,
            'display_tmdb_rating' => 1,
            'display_imdb_rating' => 1,
            'movie_title_display' => 'localized',
            'mastodon_enabled' => 0,
            'mastodon_username' => null,
            'mastodon_access_token' => null,
            'mastodon_post_automatic' => 1,
            'mastodon_post_visibility' => 'public',
        ];
    }
}
