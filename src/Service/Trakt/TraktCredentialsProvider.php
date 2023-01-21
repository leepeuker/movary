<?php declare(strict_types=1);

namespace Movary\Service\Trakt;

use Movary\Api\Trakt\ValueObject\TraktCredentials;
use Movary\Domain\User\UserApi;
use Movary\Service\Trakt\Exception\TraktClientIdNotSet;
use Movary\Service\Trakt\Exception\TraktUsernameNotSet;

class TraktCredentialsProvider
{
    public function __construct(private readonly UserApi $userApi)
    {
    }

    /**
     * @throws TraktUsernameNotSet
     * @throws TraktClientIdNotSet
     */
    public function fetchTraktCredentialsByUserId(int $userId) : TraktCredentials
    {
        $traktClientId = $this->userApi->findTraktClientId($userId);
        if ($traktClientId === null) {
            throw new TraktClientIdNotSet();
        }

        $traktUsername = $this->userApi->findTraktUserName($userId);
        if ($traktUsername === null) {
            throw new TraktUsernameNotSet();
        }

        return TraktCredentials::create($traktUsername, $traktClientId);
    }
}
