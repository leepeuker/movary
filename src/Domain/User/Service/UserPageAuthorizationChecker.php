<?php declare(strict_types=1);

namespace Movary\Domain\User\Service;

use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;

class UserPageAuthorizationChecker
{
    public function __construct(
        private readonly UserApi $userApi,
        private readonly Authentication $authenticationService,
    ) {
    }

    public function fetchAllHavingWatchedMovieVisibleUsernamesForCurrentVisitor(int $movieId) : array
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return $this->userApi->fetchAllHavingWatchedMoviePublicVisibleUsernames($movieId);
        }

        return $this->userApi->fetchAllHavingWatchedMovieInternVisibleUsernames($movieId);
    }

    public function fetchAllHavingWatchedMovieWithPersonVisibleUsernamesForCurrentVisitor(int $personId) : array
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return $this->userApi->fetchAllHavingWatchedMovieWithPersonPublicVisibleUsernames($personId);
        }

        return $this->userApi->fetchAllHavingWatchedMovieWithPersonInternVisibleUsernames($personId);
    }

    public function fetchAllVisibleUsernamesForCurrentVisitor() : array
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return $this->userApi->fetchAllPublicVisibleUsernames();
        }

        return $this->userApi->fetchAllInternVisibleUsernames();
    }

    public function findUserIdIfCurrentVisitorIsAllowedToSeeUser(string $username) : ?int
    {
        return $this->findUserIfCurrentVisitorIsAllowedToSeeUser($username)?->getId();
    }

    public function findUserIfCurrentVisitorIsAllowedToSeeUser(string $username) : ?UserEntity
    {
        $user = $this->userApi->findUserByName($username);
        if ($user === null) {
            return null;
        }

        $userId = $user->getId();

        if ($this->authenticationService->isUserPageVisibleForCurrentUser($user->getPrivacyLevel(), $userId) === false) {
            return null;
        }

        return $user;
    }
}
