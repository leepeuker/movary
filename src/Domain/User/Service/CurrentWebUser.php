<?php declare(strict_types=1);

namespace Movary\Domain\User\Service;

use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;

class CurrentWebUser
{
    private ?UserEntity $user = null;

    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly UserApi $userApi,
    ) {
    }

    public function findUser() : ?UserEntity
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $authenticatedUser = $this->authenticationService->authenticateWebSession();
        if ($authenticatedUser === null) {
            return null;
        }

        $this->user = $this->userApi->fetchUser($authenticatedUser->getUserId());

        return $this->user;
    }

    public function requireUser() : UserEntity
    {
        if ($this->user === null) {
            $authenticatedUser = $this->authenticationService->requireWebSession();
            $this->user = $this->userApi->fetchUser($authenticatedUser->getUserId());
        }

        return $this->user;
    }
}
