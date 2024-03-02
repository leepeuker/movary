<?php declare(strict_types=1);

namespace Movary\Domain\User\Service;

use Movary\Domain\User\UserApi;
use Movary\ValueObject\Http\Request;

class UserPageAuthorizationChecker
{
    public function __construct(
        private readonly UserApi $userApi,
        private readonly AuthenticationInterface $authenticationService,
    ) {
    }

    public function fetchAllHavingWatchedMovieVisibleUsernamesForCurrentVisitor(Request $request, int $movieId) : array
    {
        if ($this->authenticationService->isUserAuthenticated($request) === false) {
            return $this->userApi->fetchAllHavingWatchedMoviePublicVisibleUsernames($movieId);
        }

        return $this->userApi->fetchAllHavingWatchedMovieInternVisibleUsernames($movieId);
    }

    public function fetchAllHavingWatchedMovieWithPersonVisibleUsernamesForCurrentVisitor(Request $request, int $personId) : array
    {
        if ($this->authenticationService->isUserAuthenticated($request) === false) {
            return $this->userApi->fetchAllHavingWatchedMovieWithPersonPublicVisibleUsernames($personId);
        }

        return $this->userApi->fetchAllHavingWatchedMovieWithPersonInternVisibleUsernames($personId);
    }

    public function fetchAllVisibleUsernamesForCurrentVisitor(Request $request) : array
    {
        if ($this->authenticationService->isUserAuthenticated($request) === false) {
            return $this->userApi->fetchAllPublicVisibleUsernames();
        }

        return $this->userApi->fetchAllInternVisibleUsernames();
    }

    public function findUserIdIfCurrentVisitorIsAllowedToSeeUser(Request $request, string $username) : ?int
    {
        $user = $this->userApi->findUserByName($username);
        if ($user === null) {
            return null;
        }

        $userId = $user->getId();

        if ($this->isUserPageVisibleForCurrentUser($request, $user->getPrivacyLevel(), $userId) === false) {
            return null;
        }

        return $user->getId();
    }

    private function isUserPageVisibleForCurrentUser(Request $request, int $privacyLevel, int $userId) : bool
    {
        if ($privacyLevel === 2) {
            return true;
        }

        $isUserAuthenticated = $this->authenticationService->isUserAuthenticated($request);

        if ($privacyLevel === 1 && $isUserAuthenticated === true) {
            return true;
        }

        return $isUserAuthenticated === true && $this->authenticationService->getCurrentUser($request)->getId() === $userId;
    }
}
