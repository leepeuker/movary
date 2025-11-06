<?php declare(strict_types=1);

namespace Movary\Service\Mastodon;

use Movary\Domain\User\UserApi;
use RuntimeException;

class MastodonPostService
{
    public function __construct(
        private readonly MastodonApi $mastodonApi,
        private readonly UserApi $userApi,
    ) {
    }

    public function postMessageForUser(int $userId, string $message) : void
    {
        $user = $this->userApi->fetchUser($userId);

        $mastodonApiToken = $user->getMastodonAccessToken();
        if ($mastodonApiToken === null) {
            throw new RuntimeException('Mastodon access token missing');
        }

        $mastodonUsername = $user->getMastodonUsername();
        if ($mastodonUsername === null) {
            throw new RuntimeException('Mastodon username missing');
        }

        $mastodonPostVisibility = $user->getMastodonPostVisibility();
        if ($mastodonPostVisibility === null) {
            throw new RuntimeException('Mastodon post visibility missing');
        }

        $this->mastodonApi->createPost($mastodonApiToken, $mastodonUsername, $mastodonPostVisibility, $message);
    }
}
